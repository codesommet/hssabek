<?php

namespace App\Http\Controllers\Backoffice\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\Store\StoreMoneyTransferRequest;
use App\Http\Requests\Finance\Update\UpdateMoneyTransferRequest;
use App\Models\Finance\BankAccount;
use App\Models\Finance\MoneyTransfer;
use App\Services\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MoneyTransferController extends Controller
{
    public function index(Request $request)
    {
        $transfers = MoneyTransfer::query()
            ->with(['fromBankAccount', 'toBankAccount'])
            ->when($request->search, fn($q, $s) => $q->where('reference_number', 'like', "%{$s}%"))
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->latest('transfer_date')
            ->paginate(15)
            ->withQueryString();

        return view('backoffice.finance.money-transfers.index', compact('transfers'));
    }

    public function create()
    {
        $bankAccounts = BankAccount::where('is_active', true)->orderBy('bank_name')->get();

        return view('backoffice.finance.money-transfers.create', compact('bankAccounts'));
    }

    public function store(StoreMoneyTransferRequest $request)
    {
        $data = $request->validated();
        $data['status'] = 'completed';

        $transfer = DB::transaction(function () use ($data) {
            // Lock both accounts to prevent race conditions
            $fromAccount = BankAccount::where('id', $data['from_bank_account_id'])->lockForUpdate()->firstOrFail();
            $toAccount = BankAccount::where('id', $data['to_bank_account_id'])->lockForUpdate()->firstOrFail();

            $transfer = MoneyTransfer::create($data);

            // Deduct from source
            $fromAccount->decrement('current_balance', $transfer->amount);

            // Add to destination
            $toAccount->increment('current_balance', $transfer->amount);

            return $transfer;
        });

        return redirect()->route('bo.finance.money-transfers.show', $transfer)
            ->with('success', 'Transfert effectué avec succès.');
    }

    public function show(MoneyTransfer $moneyTransfer)
    {
        abort_unless($moneyTransfer->tenant_id === TenantContext::id(), 403);

        $moneyTransfer->load(['fromBankAccount', 'toBankAccount']);

        return view('backoffice.finance.money-transfers.show', compact('moneyTransfer'));
    }

    public function edit(MoneyTransfer $moneyTransfer)
    {
        abort_unless($moneyTransfer->tenant_id === TenantContext::id(), 403);

        $bankAccounts = BankAccount::where('is_active', true)->orderBy('bank_name')->get();

        return view('backoffice.finance.money-transfers.edit', compact('moneyTransfer', 'bankAccounts'));
    }

    public function update(UpdateMoneyTransferRequest $request, MoneyTransfer $moneyTransfer)
    {
        abort_unless($moneyTransfer->tenant_id === TenantContext::id(), 403);

        $data = $request->validated();

        DB::transaction(function () use ($data, $moneyTransfer) {
            // Reverse old transfer
            $oldFrom = BankAccount::where('id', $moneyTransfer->from_bank_account_id)->lockForUpdate()->firstOrFail();
            $oldTo = BankAccount::where('id', $moneyTransfer->to_bank_account_id)->lockForUpdate()->firstOrFail();
            $oldFrom->increment('current_balance', $moneyTransfer->amount);
            $oldTo->decrement('current_balance', $moneyTransfer->amount);

            $moneyTransfer->update($data);

            // Apply new transfer
            $newFrom = BankAccount::where('id', $moneyTransfer->from_bank_account_id)->lockForUpdate()->firstOrFail();
            $newTo = BankAccount::where('id', $moneyTransfer->to_bank_account_id)->lockForUpdate()->firstOrFail();
            $newFrom->decrement('current_balance', $moneyTransfer->amount);
            $newTo->increment('current_balance', $moneyTransfer->amount);
        });

        return redirect()->route('bo.finance.money-transfers.show', $moneyTransfer)
            ->with('success', 'Transfert modifié avec succès.');
    }

    public function destroy(MoneyTransfer $moneyTransfer)
    {
        abort_unless($moneyTransfer->tenant_id === TenantContext::id(), 403);

        DB::transaction(function () use ($moneyTransfer) {
            // Reverse the transfer
            $fromAccount = BankAccount::where('id', $moneyTransfer->from_bank_account_id)->lockForUpdate()->firstOrFail();
            $toAccount = BankAccount::where('id', $moneyTransfer->to_bank_account_id)->lockForUpdate()->firstOrFail();
            $fromAccount->increment('current_balance', $moneyTransfer->amount);
            $toAccount->decrement('current_balance', $moneyTransfer->amount);

            $moneyTransfer->delete();
        });

        return redirect()->route('bo.finance.money-transfers.index')
            ->with('success', 'Transfert supprimé avec succès.');
    }
}
