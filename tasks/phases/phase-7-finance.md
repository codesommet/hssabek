# Phase 7 — Finance (Bank Accounts, Expenses, Income, Currency)

> **Depends on:** Phase 0, Phase 4 (payments link to bank accounts)
> **Complexity:** M

---

## 1. Objective

Track business finances:
1. **Bank Accounts** — track balances tied to incoming/outgoing payments
2. **Expenses** — categorized expenses with bank account linkage
3. **Finance Categories** — income and expense classification
4. **Currency** — multi-currency support with exchange rates

---

## 2. Scope

**Route file:** `routes/backoffice/finance.php` (currently empty)
**Controllers (rewrite):**
- `app/Http/Controllers/Backoffice/Finance/BankAccountController.php`
- `app/Http/Controllers/Backoffice/Finance/ExpenseController.php`
- `app/Http/Controllers/Backoffice/Finance/FinanceCategoryController.php`
- `app/Http/Controllers/Backoffice/Finance/CurrencyController.php`

**Models (existing — do not modify):**
- `BankAccount` — `tenant_id`, `name`, `account_number`, `bank_name`, `currency_id`, `opening_balance`, `current_balance`, `status`
- `Expense` — `tenant_id`, `finance_category_id`, `bank_account_id`, `amount`, `expense_date`, `reference`, `description`, `status`, `payment_method`
- `FinanceCategory` — `tenant_id`, `name`, `type` (income/expense), `description`
- `Currency` — `tenant_id`, `code`, `name`, `symbol`, `exchange_rate`, `is_base`, `status`
- `ExchangeRate` — `tenant_id`, `from_currency_id`, `to_currency_id`, `rate`, `rate_date`
- `Income` — `tenant_id`, `finance_category_id`, `bank_account_id`, `amount`, `income_date`, `description`, `reference`
- `MoneyTransfer` — `tenant_id`, `from_account_id`, `to_account_id`, `amount`, `transfer_date`, `reference`, `notes`

---

## 3. Required Architectural Placement

| Concern | File | Type |
|---------|------|------|
| Currency conversion | `app/Services/Finance/CurrencyService.php` | NEW Service |
| Bank balance update | Inline in controllers (simple enough) | Controller |
| Authorization | `app/Policies/BankAccountPolicy.php`, `ExpensePolicy.php` | NEW Policies |
| Validation | Existing FormRequests in `Finance/` — audit French messages | FormRequests |

---

## 4. Ordered Task Breakdown

### Task 7.1 — Fill `routes/backoffice/finance.php`

```php
<?php

use App\Http\Controllers\Backoffice\Finance\{BankAccountController, ExpenseController, FinanceCategoryController, CurrencyController};
use Illuminate\Support\Facades\Route;

Route::prefix('finance')->as('finance.')->group(function () {
    Route::resource('bank-accounts',      BankAccountController::class);
    Route::resource('expenses',           ExpenseController::class);
    Route::resource('finance-categories', FinanceCategoryController::class)->except(['show']);
    Route::resource('currencies',         CurrencyController::class)->except(['show']);
    Route::post('currencies/{currency}/set-base',
        [CurrencyController::class, 'setBase'])->name('currencies.set-base');
});
```

### Task 7.2 — Implement `CurrencyService`

```php
// app/Services/Finance/CurrencyService.php
<?php

namespace App\Services\Finance;

use App\Models\Finance\Currency;
use App\Models\Finance\ExchangeRate;
use App\Services\Tenancy\TenantContext;

class CurrencyService
{
    /**
     * Convert an amount from one currency to another using the latest exchange rate.
     */
    public function convert(float $amount, string $fromCode, string $toCode): float
    {
        if ($fromCode === $toCode) return round($amount, 2);

        $tenantId = TenantContext::id();

        $rate = ExchangeRate::where('tenant_id', $tenantId)
            ->whereHas('fromCurrency', fn($q) => $q->where('code', $fromCode))
            ->whereHas('toCurrency', fn($q) => $q->where('code', $toCode))
            ->latest('rate_date')
            ->value('rate');

        if (!$rate) {
            throw new \RuntimeException(
                "Taux de change introuvable : {$fromCode} → {$toCode}"
            );
        }

        return round($amount * $rate, 2);
    }

    /**
     * Get the base currency for the current tenant.
     */
    public function baseCurrency(): Currency
    {
        return Currency::where('tenant_id', TenantContext::id())
            ->where('is_base', true)
            ->firstOrFail();
    }
}
```

### Task 7.3 — Implement `BankAccountController`

```php
public function index(Request $request)
{
    abort_unless(auth()->user()->can('finance.bank_accounts.view'), 403);

    $accounts = BankAccount::query()
        ->with('currency')
        ->when($request->status, fn($q, $s) => $q->where('status', $s))
        ->orderBy('name')
        ->paginate(15)
        ->withQueryString();

    return view('backoffice.finance.bank-accounts.index', compact('accounts'));
}

public function store(StoreBankAccountRequest $request)
{
    abort_unless(auth()->user()->can('finance.bank_accounts.create'), 403);

    $account = BankAccount::create($request->validated());
    // Opening balance IS the current balance at creation
    $account->update(['current_balance' => $account->opening_balance]);

    return redirect()->route('bo.finance.bank-accounts.index')
        ->with('success', 'Compte bancaire ajouté.');
}
```

### Task 7.4 — Implement `ExpenseController`

```php
public function store(StoreExpenseRequest $request)
{
    abort_unless(auth()->user()->can('finance.expenses.create'), 403);

    $expense = Expense::create($request->validated());

    // Deduct from bank account balance
    if ($expense->bank_account_id) {
        BankAccount::where('id', $expense->bank_account_id)
            ->decrement('current_balance', $expense->amount);
    }

    return redirect()->route('bo.finance.expenses.index')
        ->with('success', 'Dépense enregistrée.');
}

public function destroy(Expense $expense)
{
    abort_unless(auth()->user()->can('finance.expenses.delete'), 403);

    // Restore bank account balance before deletion
    if ($expense->bank_account_id) {
        BankAccount::where('id', $expense->bank_account_id)
            ->increment('current_balance', $expense->amount);
    }

    $expense->delete();
    return redirect()->route('bo.finance.expenses.index')
        ->with('success', 'Dépense supprimée.');
}
```

### Task 7.5 — Audit FormRequests (French Messages)

Check all Finance FormRequests:
- `StoreBankAccountRequest`, `UpdateBankAccountRequest`
- `StoreExpenseRequest`, `UpdateExpenseRequest`
- `StoreCurrencyRequest`, `UpdateCurrencyRequest`
- `StoreFinanceCategoryRequest`, `UpdateFinanceCategoryRequest`

Remove `tenant_id` from all `rules()`. Add French `messages()`.
Validate `bank_account_id` and `finance_category_id` with tenant-scoped `Rule::exists()`.

### Task 7.6 — Create Blade Views

Reference templates:
- Bank accounts → `resources/views/bank-accounts.blade.php`
- Expenses → `resources/views/expenses.blade.php`
- Finance categories → `resources/views/company-settings.blade.php` (settings tab pattern)
- Currencies → settings tab pattern

---

## 5. Deliverables

| File | Action |
|------|--------|
| `routes/backoffice/finance.php` | Filled |
| `app/Services/Finance/CurrencyService.php` | New |
| `app/Http/Controllers/Backoffice/Finance/BankAccountController.php` | Rewritten |
| `app/Http/Controllers/Backoffice/Finance/ExpenseController.php` | Rewritten |
| `app/Http/Controllers/Backoffice/Finance/FinanceCategoryController.php` | Rewritten |
| `app/Http/Controllers/Backoffice/Finance/CurrencyController.php` | Rewritten |
| `app/Policies/BankAccountPolicy.php`, `ExpensePolicy.php` | New |
| All Finance Blade views | New |

---

## 6. Acceptance Criteria

- [ ] Bank account balance decremented when expense recorded
- [ ] Bank account balance restored when expense deleted
- [ ] Currency conversion works for pairs with exchange rates configured
- [ ] `CurrencyService::convert()` throws when no rate found
- [ ] Only one currency can have `is_base = true` per tenant
- [ ] Finance categories typed correctly (income vs expense) in filter dropdowns
- [ ] All `finance.*` permissions enforced

---

## 7. Tests Required

| Test File | Type | Covers |
|-----------|------|--------|
| `tests/Feature/Finance/BankAccountTest.php` | Feature | Balance tracking |
| `tests/Feature/Finance/ExpenseTest.php` | Feature | Expense creation + balance deduction |
| `tests/Unit/Services/CurrencyServiceTest.php` | Unit | Conversion math |

---

## 8. Multi-Tenant Pitfalls

- `BankAccount` current_balance update: use `increment/decrement` not `update(['current_balance' => X])` to prevent race conditions
- Validate `bank_account_id` in `StoreExpenseRequest` with `.where('tenant_id', TenantContext::id())`
- Only `is_base = true` currencies affect the default display — ensure only one per tenant

---

## 9. Schema Notes

**`bank_accounts` columns:** `tenant_id`, `name`, `account_number`, `bank_name`, `currency_id`, `opening_balance` (decimal:2), `current_balance` (decimal:2), `status`

**`expenses` columns:** `tenant_id`, `finance_category_id`, `bank_account_id` (nullable), `amount` (decimal:2), `expense_date`, `reference`, `description`, `status`, `payment_method`

Use `increment()` / `decrement()` for balance updates — never set balance directly unless recalculating from scratch.

---

## 10. UI Instructions

- **Bank accounts reference:** `resources/views/bank-accounts.blade.php`
- **Expenses reference:** `resources/views/expenses.blade.php`
- Show current balance prominently on bank account card/row
- Expense form: show remaining bank account balance when account is selected (via JS or inline info)
- Currency list: mark base currency with a "Base" badge
