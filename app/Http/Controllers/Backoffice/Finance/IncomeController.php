<?php

namespace App\Http\Controllers\Backoffice\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\Store\StoreIncomeRequest;
use App\Http\Requests\Finance\Update\UpdateIncomeRequest;
use App\Models\CRM\Customer;
use App\Models\Finance\BankAccount;
use App\Models\Finance\FinanceCategory;
use App\Models\Finance\Income;
use App\Services\System\DocumentNumberService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IncomeController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Income::class);

        $incomes = Income::query()
            ->with(['category', 'bankAccount', 'customer'])
            ->when($request->search, fn($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('income_number', 'like', "%{$s}%")
                    ->orWhere('reference_number', 'like', "%{$s}%")
                    ->orWhere('description', 'like', "%{$s}%");
            }))
            ->when($request->category_id, fn($q, $c) => $q->where('category_id', $c))
            ->latest('income_date')
            ->paginate(15)
            ->withQueryString();

        $categories = FinanceCategory::where('type', 'income')->orderBy('name')->get();

        return view('backoffice.finance.incomes.index', compact('incomes', 'categories'));
    }

    public function create()
    {
        $this->authorize('create', Income::class);

        $categories = FinanceCategory::where('type', 'income')->where('is_active', true)->orderBy('name')->get();
        $bankAccounts = BankAccount::where('is_active', true)->orderBy('bank_name')->get();
        $customers = Customer::orderBy('name')->get();

        return view('backoffice.finance.incomes.create', compact('categories', 'bankAccounts', 'customers'));
    }

    public function store(StoreIncomeRequest $request)
    {
        $this->authorize('create', Income::class);

        $data = $request->validated();
        $data['income_number'] = app(DocumentNumberService::class)->next('income');

        $income = DB::transaction(function () use ($data) {
            $income = Income::create($data);

            if ($income->bank_account_id) {
                $account = BankAccount::where('id', $income->bank_account_id)
                    ->lockForUpdate()
                    ->firstOrFail();
                $account->increment('current_balance', $income->amount);
            }

            return $income;
        });

        return redirect()->route('bo.finance.incomes.index')
            ->with('success', 'Revenu enregistré avec succès.');
    }

    public function edit(Income $income)
    {
        $this->authorize('update', $income);

        $categories = FinanceCategory::where('type', 'income')->where('is_active', true)->orderBy('name')->get();
        $bankAccounts = BankAccount::where('is_active', true)->orderBy('bank_name')->get();
        $customers = Customer::orderBy('name')->get();

        return view('backoffice.finance.incomes.edit', compact('income', 'categories', 'bankAccounts', 'customers'));
    }

    public function update(UpdateIncomeRequest $request, Income $income)
    {
        $this->authorize('update', $income);

        DB::transaction(function () use ($request, $income) {
            $oldBankAccountId = $income->bank_account_id;
            $oldAmount = $income->amount;

            // Lock all involved bank accounts upfront
            $accountIds = array_filter(array_unique([
                $oldBankAccountId,
                $request->validated('bank_account_id', $oldBankAccountId),
            ]));

            if (!empty($accountIds)) {
                BankAccount::whereIn('id', $accountIds)->lockForUpdate()->get();
            }

            // Restore old bank balance
            if ($oldBankAccountId) {
                BankAccount::where('id', $oldBankAccountId)
                    ->decrement('current_balance', $oldAmount);
            }

            $income->update($request->validated());

            // Add new amount
            if ($income->bank_account_id) {
                BankAccount::where('id', $income->bank_account_id)
                    ->increment('current_balance', $income->amount);
            }
        });

        return redirect()->route('bo.finance.incomes.index')
            ->with('success', 'Revenu mis à jour avec succès.');
    }

    public function destroy(Income $income)
    {
        $this->authorize('delete', $income);

        DB::transaction(function () use ($income) {
            if ($income->bank_account_id) {
                $account = BankAccount::where('id', $income->bank_account_id)
                    ->lockForUpdate()
                    ->firstOrFail();
                $account->decrement('current_balance', $income->amount);
            }

            $income->delete();
        });

        return redirect()->route('bo.finance.incomes.index')
            ->with('success', 'Revenu supprimé avec succès.');
    }
}
