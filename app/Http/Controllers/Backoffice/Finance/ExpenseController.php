<?php

namespace App\Http\Controllers\Backoffice\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\Store\StoreExpenseRequest;
use App\Http\Requests\Finance\Update\UpdateExpenseRequest;
use App\Models\Finance\BankAccount;
use App\Models\Finance\Expense;
use App\Models\Finance\FinanceCategory;
use App\Models\Purchases\Supplier;
use App\Services\System\DocumentNumberService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Expense::class);

        $expenses = Expense::query()
            ->with(['category', 'bankAccount', 'supplier'])
            ->when($request->search, fn($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('expense_number', 'like', "%{$s}%")
                    ->orWhere('reference_number', 'like', "%{$s}%")
                    ->orWhere('description', 'like', "%{$s}%");
            }))
            ->when($request->category_id, fn($q, $c) => $q->where('category_id', $c))
            ->when($request->payment_status, fn($q, $s) => $q->where('payment_status', $s))
            ->latest('expense_date')
            ->paginate(15)
            ->withQueryString();

        $categories = FinanceCategory::where('type', 'expense')->orderBy('name')->get();

        return view('backoffice.finance.expenses.index', compact('expenses', 'categories'));
    }

    public function create()
    {
        $this->authorize('create', Expense::class);

        $categories = FinanceCategory::where('type', 'expense')->where('is_active', true)->orderBy('name')->get();
        $bankAccounts = BankAccount::where('is_active', true)->orderBy('bank_name')->get();
        $suppliers = Supplier::orderBy('name')->get();

        return view('backoffice.finance.expenses.create', compact('categories', 'bankAccounts', 'suppliers'));
    }

    public function store(StoreExpenseRequest $request)
    {
        $this->authorize('create', Expense::class);

        $data = $request->validated();
        $data['expense_number'] = app(DocumentNumberService::class)->next('expense');

        $expense = DB::transaction(function () use ($data) {
            $expense = Expense::create($data);

            // Deduct from bank account if paid and linked
            if ($expense->bank_account_id && $expense->payment_status === 'paid') {
                $account = BankAccount::where('id', $expense->bank_account_id)
                    ->lockForUpdate()
                    ->firstOrFail();
                $account->decrement('current_balance', $expense->amount);
            }

            return $expense;
        });

        return redirect()->route('bo.finance.expenses.index')
            ->with('success', 'Dépense enregistrée avec succès.');
    }

    public function edit(Expense $expense)
    {
        $this->authorize('update', $expense);

        $categories = FinanceCategory::where('type', 'expense')->where('is_active', true)->orderBy('name')->get();
        $bankAccounts = BankAccount::where('is_active', true)->orderBy('bank_name')->get();
        $suppliers = Supplier::orderBy('name')->get();

        return view('backoffice.finance.expenses.edit', compact('expense', 'categories', 'bankAccounts', 'suppliers'));
    }

    public function update(UpdateExpenseRequest $request, Expense $expense)
    {
        $this->authorize('update', $expense);

        DB::transaction(function () use ($request, $expense) {
            $oldBankAccountId = $expense->bank_account_id;
            $oldAmount = $expense->amount;
            $oldPaymentStatus = $expense->payment_status;

            // Lock all involved bank accounts upfront
            $accountIds = array_filter(array_unique([
                $oldBankAccountId,
                $request->validated('bank_account_id', $oldBankAccountId),
            ]));

            if (!empty($accountIds)) {
                BankAccount::whereIn('id', $accountIds)->lockForUpdate()->get();
            }

            // Restore old bank balance if it was paid
            if ($oldBankAccountId && $oldPaymentStatus === 'paid') {
                BankAccount::where('id', $oldBankAccountId)
                    ->increment('current_balance', $oldAmount);
            }

            $expense->update($request->validated());

            // Deduct new amount if paid
            if ($expense->bank_account_id && $expense->payment_status === 'paid') {
                BankAccount::where('id', $expense->bank_account_id)
                    ->decrement('current_balance', $expense->amount);
            }
        });

        return redirect()->route('bo.finance.expenses.index')
            ->with('success', 'Dépense mise à jour avec succès.');
    }

    public function destroy(Expense $expense)
    {
        $this->authorize('delete', $expense);

        DB::transaction(function () use ($expense) {
            // Restore bank balance before deletion
            if ($expense->bank_account_id && $expense->payment_status === 'paid') {
                $account = BankAccount::where('id', $expense->bank_account_id)
                    ->lockForUpdate()
                    ->firstOrFail();
                $account->increment('current_balance', $expense->amount);
            }

            $expense->delete();
        });

        return redirect()->route('bo.finance.expenses.index')
            ->with('success', 'Dépense supprimée avec succès.');
    }
}
