# AI Implementation Prompts — Facturation SaaS

> **Purpose:** Structured prompts to complete the remaining ~30% of the SaaS application.
> Each section is self-contained and copy-paste ready for AI coding tools.
>
> **Project context (include in every prompt):**
> - Laravel 11, PHP 8.2+, Blade templates, Bootstrap CSS theme
> - Multi-tenant SaaS with UUID primary keys (`HasUuids` trait)
> - Tenant isolation via `BelongsToTenant` trait + `TenantContext::id()`
> - Service layer pattern (no repositories)
> - Policies for authorization (`$this->authorize()`)
> - FormRequest validation with French messages
> - Routes prefixed `bo.*` (backoffice)
> - Settings stored as JSON in `tenant_settings` table via `TenantSetting` model

---

# TABLE OF CONTENTS

1. [Settings Module — Remaining Stubs](#section-1--settings-module)
2. [Finance Missing CRUD — Supplier Payment Methods](#section-2--finance-missing-crud)
3. [Loan Installments UI](#section-3--loan-installments-ui)
4. [System Log Viewers](#section-4--system-log-viewers)
5. [Template Marketplace](#section-5--template-marketplace)
6. [Reports Module](#section-6--reports-module)
7. [Missing Services](#section-7--missing-services)
8. [High Value SaaS Features](#section-8--high-value-saas-features)

---

# SECTION 1 — SETTINGS MODULE

## Explanation

After audit, **11 of 13 settings controllers are fully implemented**. Only 2 remain as stubs:

| Controller | Status | What's Missing |
|------------|--------|----------------|
| `SecuritySettingsController` | STUB | Only displays the page — no 2FA, no device management, no account disable/delete |
| `PlansBillingsController` | STUB | Only reads subscription — no plan change, no payment history download |

All other settings controllers (Company, Invoice, Locale, Appearance, Barcode, Currencies, Email Templates, Invoice Templates, Notifications, Payment Methods, Signatures) are **fully implemented** with real CRUD logic.

**Views already exist at:** `resources/views/backoffice/settings/`
**Routes already exist at:** `routes/backoffice/settings.php`
**Sidebar already wired:** `resources/views/backoffice/components/settings-sidebar.blade.php`

---

## 1A — Security Settings Controller

### Architecture Plan

The security page (`resources/views/backoffice/settings/security.blade.php`) shows:
- Password change (already handled by `AccountSettingsController::updatePassword`)
- Two-Factor Authentication toggle
- Phone/Email verification status
- Active sessions / devices list
- Disable account action
- Delete account action

**Files to modify:**
- `app/Http/Controllers/Backoffice/Settings/SecuritySettingsController.php` — add methods
- `routes/backoffice/settings.php` — add POST/PUT/DELETE routes under `settings.security.*`

### Implementation Prompt

```
You are a senior Laravel 11 developer working on a multi-tenant SaaS application.

CONTEXT:
- Multi-tenant with UUID primary keys (HasUuids trait)
- Tenant isolation via BelongsToTenant trait + TenantContext::id()
- Settings stored as JSON in tenant_settings table (TenantSetting model with JSON cast columns)
- The security settings view already exists at: resources/views/backoffice/settings/security.blade.php
- The current SecuritySettingsController is a stub with only an index() method
- User model is at app/Models/User.php with SoftDeletes trait
- Sessions table exists (Laravel default)
- All user-facing strings must be in FRENCH

EXISTING CONTROLLER (app/Http/Controllers/Backoffice/Settings/SecuritySettingsController.php):
```php
<?php
namespace App\Http\Controllers\Backoffice\Settings;
use App\Http\Controllers\Controller;
use App\Services\Tenancy\TenantContext;

class SecuritySettingsController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $tenant = TenantContext::get();
        $settings = $tenant->settings;
        return view('backoffice.settings.security', compact('user', 'settings'));
    }
}
```

TASK:
Expand SecuritySettingsController with these methods:

1. `activeSessions()` — Query the `sessions` table for current user's active sessions (ip_address, user_agent, last_activity). Return JSON or pass to view.

2. `revokeSession(Request $request, string $sessionId)` — Delete a specific session from the sessions table (except the current one). Redirect back with French success message.

3. `revokeAllSessions()` — Delete all sessions for current user EXCEPT the current session. Redirect back with French success message.

4. `disableAccount()` — Set user status to 'blocked', log them out, redirect to login with French message "Votre compte a été désactivé."

5. `deleteAccount(Request $request)` — Validate password confirmation. Soft-delete the user, log them out, redirect to login with French message "Votre compte a été supprimé définitivement."

Also add these routes in routes/backoffice/settings.php under the security group:
- GET /settings/security/sessions → activeSessions → settings.security.sessions
- DELETE /settings/security/sessions/{session} → revokeSession → settings.security.sessions.revoke
- DELETE /settings/security/sessions → revokeAllSessions → settings.security.sessions.revoke-all
- POST /settings/security/disable-account → disableAccount → settings.security.disable
- DELETE /settings/security/delete-account → deleteAccount → settings.security.delete

DO NOT modify the Blade view — only create the controller methods and routes.
All flash messages must be in French.
Use DB::table('sessions') for session queries.
Current session ID: session()->getId()
```

### Expected Result

The security settings page becomes functional with:
- Users can view and revoke individual browser sessions
- Users can revoke all other sessions at once
- Users can disable their own account (soft block)
- Users can permanently delete their account (soft delete + logout)

---

## 1B — Plans & Billings Settings Controller

### Architecture Plan

The plans & billings page shows the tenant's current subscription, billing history, and plan upgrade options.

**Files to modify:**
- `app/Http/Controllers/Backoffice/Settings/PlansBillingsController.php` — add methods
- `routes/backoffice/settings.php` — add routes under `settings.plans-billings.*`

### Implementation Prompt

```
You are a senior Laravel 11 developer working on a multi-tenant SaaS application.

CONTEXT:
- Multi-tenant with UUID primary keys
- Tenant isolation via TenantContext
- Existing models:
  - App\Models\Billing\Plan (fillable: name, code, interval, price, currency, trial_days, is_active, features)
  - App\Models\Billing\Subscription (fillable: tenant_id, plan_id, status, starts_at, ends_at, trial_ends_at, cancels_at)
  - App\Models\Billing\SubscriptionInvoice (fillable: tenant_id, subscription_id, invoice_number, amount, status, issued_at, due_at, paid_at)
- View already exists: resources/views/backoffice/settings/plans-billings.blade.php
- All user-facing strings must be in FRENCH

EXISTING CONTROLLER:
```php
<?php
namespace App\Http\Controllers\Backoffice\Settings;
use App\Http\Controllers\Controller;
use App\Models\Billing\Subscription;
use App\Services\Tenancy\TenantContext;

class PlansBillingsController extends Controller
{
    public function index()
    {
        $tenant = TenantContext::get();
        $currentSubscription = Subscription::with('plan')
            ->where('status', '!=', 'cancelled')
            ->latest('starts_at')
            ->first();
        $subscriptionHistory = Subscription::with('plan')
            ->latest('starts_at')
            ->get();
        return view('backoffice.settings.plans-billings', compact('currentSubscription', 'subscriptionHistory'));
    }
}
```

TASK:
Expand PlansBillingsController with:

1. Update `index()` to also pass:
   - `$plans` — all active plans (Plan::where('is_active', true)->get())
   - `$invoices` — subscription invoices for this tenant, paginated (15 per page)
   - `$daysRemaining` — calculated from $currentSubscription->ends_at

2. `changePlan(Request $request)` — Validate plan_id exists in plans table. Update the current subscription's plan_id. Redirect back with "Plan mis à jour avec succès."

3. `cancelSubscription()` — Set current subscription's cancels_at to now(), status to 'cancelled'. Redirect back with "Votre abonnement a été annulé."

4. `downloadInvoice(SubscriptionInvoice $invoice)` — Return a simple PDF or redirect to the invoice URL. For now, just return a JSON response with the invoice data (PDF generation will be added later).

Add routes in routes/backoffice/settings.php:
- PUT /settings/plans-billings/change-plan → changePlan → settings.plans-billings.change-plan
- POST /settings/plans-billings/cancel → cancelSubscription → settings.plans-billings.cancel
- GET /settings/plans-billings/invoices/{invoice}/download → downloadInvoice → settings.plans-billings.invoice.download

All flash messages in French.
```

### Expected Result

Tenants can view their current plan, compare available plans, request a plan change, cancel their subscription, and view billing history.

---

---

# SECTION 2 — FINANCE MISSING CRUD

## Explanation

After audit, the financial module has these gaps:

| Table | Status | Notes |
|-------|--------|-------|
| `exchange_rates` | **DONE** — managed by `CurrencySettingsController` | Already has full CRUD in settings |
| `payment_methods` (Sales) | **DONE** — managed by `PaymentMethodController` in Settings | Already has full CRUD |
| `supplier_payment_methods` | **MISSING** — No controller, no views, no routes | Suppliers cannot have their own payment methods managed |

The `supplier_payment_methods` table exists with columns: `id`, `tenant_id`, `name`, `provider`, `is_active`.

However, looking at the actual model `SupplierPaymentMethod`, the fillable is: `supplier_id`, `payment_method_name`, `account_number`, `bank_name`, `is_default`. This is a **per-supplier** payment method (bank details for paying suppliers), NOT a global setting.

### Architecture Plan

Supplier payment methods should be managed **inline on the Supplier show page** (like addresses/contacts on the Customer show page). No separate index page needed.

**Files to create:**
- `app/Http/Controllers/Backoffice/Purchases/SupplierPaymentMethodController.php`
- Routes nested under supplier in `routes/backoffice/purchases.php`

**Pattern to follow:** Same as `CustomerAddressController` — store/update/destroy nested under parent.

### Implementation Prompt

```
You are a senior Laravel 11 developer working on a multi-tenant SaaS application.

CONTEXT:
- Multi-tenant with UUID primary keys (HasUuids trait)
- Tenant isolation via BelongsToTenant trait
- All user-facing strings must be in FRENCH
- The Supplier show page already exists at: resources/views/backoffice/purchases/suppliers/show.blade.php

EXISTING PATTERNS TO FOLLOW:
The CustomerAddressController pattern (nested CRUD under parent):
```php
class CustomerAddressController extends Controller
{
    public function store(StoreCustomerAddressRequest $request, Customer $customer)
    {
        $this->authorize('update', $customer);
        $customer->addresses()->create($request->validated());
        return redirect()->back()->with('success', 'Adresse ajoutée avec succès.');
    }

    public function update(UpdateCustomerAddressRequest $request, CustomerAddress $address)
    {
        $this->authorize('update', $address->customer);
        $address->update($request->validated());
        return redirect()->back()->with('success', 'Adresse mise à jour avec succès.');
    }

    public function destroy(CustomerAddress $address)
    {
        $this->authorize('update', $address->customer);
        $address->delete();
        return redirect()->back()->with('success', 'Adresse supprimée avec succès.');
    }
}
```

EXISTING MODEL (app/Models/Purchases/SupplierPaymentMethod.php):
- Table: supplier_payment_methods
- Fillable: supplier_id, payment_method_name, account_number, bank_name, is_default
- Relationships: belongsTo(Supplier), hasMany(SupplierPayment)

EXISTING MODEL (app/Models/Purchases/Supplier.php):
- Has relationship: hasMany(SupplierPaymentMethod)
- Policy: app/Policies/SupplierPolicy.php

TASK:
1. Create `app/Http/Controllers/Backoffice/Purchases/SupplierPaymentMethodController.php` with:
   - `store(Request $request, Supplier $supplier)` — Create payment method for supplier. Validate: payment_method_name (required|string|max:255), account_number (nullable|string|max:100), bank_name (nullable|string|max:255), is_default (boolean). If is_default, unset other defaults. Use $this->authorize('update', $supplier). French messages.
   - `update(Request $request, SupplierPaymentMethod $paymentMethod)` — Update. Same validation. French messages.
   - `destroy(SupplierPaymentMethod $paymentMethod)` — Delete if no supplier_payments linked. French messages.

2. Add routes in routes/backoffice/purchases.php nested under suppliers:
   - POST /suppliers/{supplier}/payment-methods → store → bo.purchases.suppliers.payment-methods.store
   - PUT /supplier-payment-methods/{paymentMethod} → update → bo.purchases.supplier-payment-methods.update
   - DELETE /supplier-payment-methods/{paymentMethod} → destroy → bo.purchases.supplier-payment-methods.destroy

3. Update the Supplier show page (resources/views/backoffice/purchases/suppliers/show.blade.php) to add a "Modes de paiement" section with:
   - A table listing existing payment methods (payment_method_name, account_number, bank_name, is_default badge, edit/delete actions)
   - An "Ajouter" button that opens a modal form
   - An edit modal form
   - Delete via form with @csrf @method('DELETE')
   - Use the existing page's HTML/CSS patterns exactly (same card, table, modal structure)

4. Make sure the SupplierController::show() method eager-loads paymentMethods:
   ```php
   $supplier->load(['paymentMethods', 'purchaseOrders', 'vendorBills']);
   ```

All validation messages in French.
```

### Expected Result

On the Supplier detail page, users can manage the supplier's bank/payment details (add, edit, delete). When creating a supplier payment, users can select from the supplier's registered payment methods.

---

---

# SECTION 3 — LOAN INSTALLMENTS UI

## Explanation

The `loan_installments` table exists with full model (`LoanInstallment`). The Loan show page (`resources/views/backoffice/finance/loans/show.blade.php`) already displays the installment schedule in a read-only table. However, there is **no action to pay an installment** — users can see the schedule but cannot record payments.

### Architecture Plan

**Files to create/modify:**
- `app/Http/Controllers/Backoffice/Finance/LoanInstallmentController.php` — `pay()` and `generateSchedule()` methods
- `app/Services/Finance/LoanService.php` — business logic for installment payments and schedule generation
- `routes/backoffice/finance.php` — add routes nested under loans
- `resources/views/backoffice/finance/loans/show.blade.php` — add pay button + modal

### Implementation Prompt

```
You are a senior Laravel 11 developer working on a multi-tenant SaaS application.

CONTEXT:
- Multi-tenant with UUID primary keys (HasUuids trait)
- Tenant isolation via BelongsToTenant trait + TenantContext::id()
- All user-facing strings must be in FRENCH
- Financial operations must use DB::transaction() + lockForUpdate()

EXISTING MODELS:

Loan (app/Models/Finance/Loan.php):
- Fillable: lender_type, lender_name, reference_number, principal_amount, interest_rate, interest_type, total_amount, remaining_balance, start_date, end_date, payment_frequency, status, notes, created_by
- Casts: principal_amount decimal:2, interest_rate decimal:3, total_amount decimal:2, remaining_balance decimal:2, start_date date, end_date date
- Relationships: hasMany(LoanInstallment), belongsTo(User, 'created_by')
- Uses traits: HasUuids, BelongsToTenant, UsesTenantCurrency

LoanInstallment (app/Models/Finance/LoanInstallment.php):
- Fillable: loan_id, installment_number, due_date, principal_amount, interest_amount, total_amount, paid_amount, remaining_amount, status, paid_at
- Casts: all amounts decimal:2, due_date date, paid_at datetime
- Status enum: pending, partial, paid, overdue
- Relationships: belongsTo(Loan)
- Uses traits: HasUuids, BelongsToTenant

EXISTING LOAN SHOW VIEW (resources/views/backoffice/finance/loans/show.blade.php):
Already displays installment schedule table with columns: N°, Date d'échéance, Principal, Intérêts, Total, Payé, Restant, Statut.
The page uses card layout with isax icons and badge-soft-* classes for status badges.

EXISTING ROUTES FILE: routes/backoffice/finance.php
Loan routes are under prefix('loans')->as('loans.') with permission middleware.

BankAccount model (app/Models/Finance/BankAccount.php):
- Has current_balance field that must be decremented on financial transactions
- Use lockForUpdate() when modifying balance

TASK:

1. Create `app/Services/Finance/LoanService.php` with:

   a) `generateSchedule(Loan $loan): void` — Generate installment records based on loan terms.
      - Calculate number of installments from start_date, end_date, and payment_frequency
      - For fixed interest: split total evenly across installments
      - For reducing balance: calculate diminishing interest per installment
      - Create LoanInstallment records with: installment_number, due_date, principal_amount, interest_amount, total_amount, paid_amount (0), remaining_amount (= total_amount), status ('pending')
      - Wrap in DB::transaction()

   b) `payInstallment(LoanInstallment $installment, float $amount, ?string $bankAccountId = null): void`
      - Validate: $amount > 0, $amount <= $installment->remaining_amount
      - Throw \DomainException if installment is already 'paid'
      - Wrap in DB::transaction():
        - Update installment: paid_amount += $amount, remaining_amount -= $amount
        - If remaining_amount <= 0.01: set status = 'paid', paid_at = now()
        - Else if paid_amount > 0: set status = 'partial'
        - Update parent Loan: remaining_balance -= $amount
        - If loan remaining_balance <= 0.01: set loan status = 'closed'
        - If $bankAccountId provided: decrement bank account balance (with lockForUpdate)
      - French error messages in DomainException

   c) `markOverdue(): void` — Static method to bulk-update installments where due_date < today and status = 'pending' to status = 'overdue'. (For scheduled command)

2. Create `app/Http/Controllers/Backoffice/Finance/LoanInstallmentController.php` with:

   a) `pay(Request $request, LoanInstallment $installment)` — Validate: amount (required|numeric|min:0.01|max:remaining), bank_account_id (nullable|uuid|exists:bank_accounts,id scoped to tenant). Call LoanService::payInstallment(). Redirect back with "Paiement enregistré avec succès."

   b) `generateSchedule(Loan $loan)` — Call LoanService::generateSchedule(). Redirect back with "Échéancier généré avec succès." Only allowed if loan has no installments yet.

3. Add routes in routes/backoffice/finance.php:
   - POST /loans/{loan}/installments/generate → generateSchedule → bo.finance.loans.installments.generate (permission: finance.loans.edit)
   - POST /loan-installments/{installment}/pay → pay → bo.finance.loan-installments.pay (permission: finance.loans.edit)

4. Update the Loan show view (resources/views/backoffice/finance/loans/show.blade.php):
   - Add a "Générer l'échéancier" button (only visible if $loan->installments->isEmpty())
   - Add a "Payer" button on each installment row (only for pending/partial/overdue status)
   - The "Payer" button opens a modal with:
     - Read-only installment info (N°, date, total, remaining)
     - Amount input (pre-filled with remaining_amount)
     - Bank account select (optional, from active bank accounts)
     - Submit button "Enregistrer le paiement"
   - Keep the exact same HTML/CSS structure, card layout, isax icons, badge classes as the existing page

5. Register LoanService as singleton in AppServiceProvider.

All messages in French. All financial ops in DB::transaction with lockForUpdate on bank accounts.
```

### Expected Result

- Users can generate an installment schedule for a new loan
- Users can record partial or full payments on each installment
- Bank account balance is automatically updated
- Loan remaining_balance auto-decrements
- Loan auto-closes when fully paid
- Overdue installments can be marked via scheduled command

---

---

# SECTION 4 — SYSTEM LOG VIEWERS

## Explanation

The application has 5 system log tables with models but **zero UI** for viewing them. This is critical for:
- **Audit compliance** — who did what and when
- **Email deliverability** — tracking sent emails
- **Security** — monitoring login attempts
- **Document management** — finding generated PDFs

### Architecture Plan

**Files to create:**
- `app/Http/Controllers/Backoffice/System/ActivityLogController.php`
- `app/Http/Controllers/Backoffice/System/EmailLogController.php`
- `app/Http/Controllers/Backoffice/System/LoginLogController.php`
- `app/Http/Controllers/Backoffice/System/NotificationLogController.php`
- `app/Http/Controllers/Backoffice/System/DocumentController.php`
- `routes/backoffice/system.php`
- `resources/views/backoffice/system/activity-logs/index.blade.php`
- `resources/views/backoffice/system/email-logs/index.blade.php`
- `resources/views/backoffice/system/login-logs/index.blade.php`
- `resources/views/backoffice/system/notification-logs/index.blade.php`
- `resources/views/backoffice/system/documents/index.blade.php`

All are **read-only** — no create/update/delete operations.

### Implementation Prompt

```
You are a senior Laravel 11 developer working on a multi-tenant SaaS application.

CONTEXT:
- Multi-tenant with UUID primary keys
- Tenant isolation via BelongsToTenant trait + TenantContext::id()
- All user-facing strings must be in FRENCH
- These are READ-ONLY viewers — no create/update/delete
- Use the same Blade layout/CSS patterns as the existing list pages

EXISTING MODELS:

ActivityLog (app/Models/System/ActivityLog.php):
- Table: activity_logs
- Fillable: tenant_id, user_id, action, subject_type, subject_id, properties (JSON), ip, user_agent
- No timestamps ($timestamps = false, created_at cast to datetime)
- Relationships: belongsTo(User), morphTo(subject)

EmailLog (app/Models/System/EmailLog.php):
- Table: email_logs
- Fillable: tenant_id, recipient_email, email_subject, email_body, status, sent_at, error_message
- Relationships: belongsTo(Tenant)

LoginLog (app/Models/System/LoginLog.php):
- Table: login_logs
- Fillable: user_id, login_time, logout_time, ip_address, user_agent
- Relationships: belongsTo(User)

NotificationLog (app/Models/System/NotificationLog.php):
- Table: notification_logs
- Fillable: tenant_id, recipient_email, notification_type, subject, message, status, sent_at
- Relationships: belongsTo(Tenant)

Document (app/Models/System/Document.php):
- Table: documents
- Fillable: tenant_id, document_type, document_number, reference_id, document_date, file_path, file_size
- Relationships: belongsTo(Tenant)

REFERENCE BLADE TEMPLATE for list pages:
Use the same structure as resources/views/backoffice/crm/customers/index.blade.php:
- @extends('backoffice.layout.mainlayout')
- @section('content') with page-wrapper > content > row
- Card with card-header (title + filter row) and card-body (table-responsive > table)
- Search input, filter selects, pagination with $items->links()
- @forelse / @empty pattern for tables
- Badge classes: badge-soft-success, badge-soft-warning, badge-soft-danger, badge-soft-info
- isax icon set (NOT ti)

EXISTING SIDEBAR (resources/views/backoffice/layout/partials/sidebar.blade.php):
Add a new "Système" section at the bottom of the sidebar with links to all 5 log viewers.

PERMISSIONS TO USE:
- settings.account.view (reuse existing settings permissions for log viewing)
- Or create new: system.logs.view

TASK:
Create all 5 read-only controllers, routes, and views.

1. Create route file `routes/backoffice/system.php`:
```php
Route::prefix('system')->as('system.')->middleware('permission:settings.account.view')->group(function () {
    Route::get('activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');
    Route::get('email-logs', [EmailLogController::class, 'index'])->name('email-logs.index');
    Route::get('login-logs', [LoginLogController::class, 'index'])->name('login-logs.index');
    Route::get('notification-logs', [NotificationLogController::class, 'index'])->name('notification-logs.index');
    Route::get('documents', [DocumentController::class, 'index'])->name('documents.index');
    Route::get('documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');
});
```

2. Include route file in routes/web.php backoffice group (same pattern as other route files).

3. Create each controller with an `index(Request $request)` method that:
   - Queries the model with search + filters + pagination (15 per page)
   - Eager loads relationships where applicable
   - Returns a Blade view

4. For each controller, implement these filters:

   **ActivityLogController:**
   - Search: action, subject_type
   - Filter: user_id (select), date range (from/to)
   - Order: latest created_at
   - Table columns: Date, Utilisateur, Action, Objet, IP

   **EmailLogController:**
   - Search: recipient_email, email_subject
   - Filter: status (queued/sent/failed), date range
   - Order: latest created_at
   - Table columns: Date, Destinataire, Sujet, Statut, Erreur

   **LoginLogController:**
   - Search: user name/email (via relationship), ip_address
   - Filter: date range
   - Order: latest login_time
   - Table columns: Date, Utilisateur, Adresse IP, Navigateur, Durée

   **NotificationLogController:**
   - Search: recipient_email, subject
   - Filter: notification_type, status (queued/sent/failed)
   - Order: latest created_at
   - Table columns: Date, Destinataire, Type, Sujet, Statut

   **DocumentController:**
   - Search: document_number
   - Filter: document_type (invoice_pdf/quote_pdf/etc.)
   - Order: latest document_date
   - Table columns: Date, Type, Numéro, Taille, Télécharger
   - `download()` method: return response()->download() for the file_path

5. Create Blade views following the exact pattern from the reference template.

6. Update the sidebar to add a "Système" section with icon isax-shield and links to all 5 pages.

All strings in French. Status badges: sent=success, failed=danger, queued=warning.
```

### Expected Result

- Activity Log: Full audit trail showing who changed what, when, and from which IP
- Email Log: Track all emails sent by the system (invoices, reminders, etc.)
- Login Log: Security view of all login attempts with IP and browser info
- Notification Log: Track all notifications across channels
- Documents: Browse and download all generated PDFs

---

---

# SECTION 5 — TEMPLATE MARKETPLACE

## Explanation

The application has a template system with 4 tables but the **SuperAdmin side** for managing the template catalog is missing. The tenant-side activation is partially handled by `InvoiceTemplateSettingsController`.

**What exists:**
- `template_catalog` table — the marketplace catalog (SuperAdmin manages)
- `template_purchases` table — premium template purchases
- `tenant_templates` table — activation per tenant
- `tenant_template_preferences` table — default template per document type
- `InvoiceTemplateSettingsController` — tenant can browse and activate/deactivate templates

**What's missing:**
- SuperAdmin CRUD for `template_catalog` (create, edit, delete templates in the marketplace)
- SuperAdmin view of `template_purchases` (see which tenants bought which templates)
- Template preview functionality
- Template purchase flow (tenant buys premium template)

### Architecture Plan

**Files to create:**
- `app/Http/Controllers/SuperAdmin/TemplateCatalogController.php`
- `app/Http/Controllers/SuperAdmin/TemplatePurchaseController.php`
- `routes/superadmin/templates.php` — update existing file
- `resources/views/backoffice/superadmin/templates/catalog/index.blade.php`
- `resources/views/backoffice/superadmin/templates/catalog/create.blade.php`
- `resources/views/backoffice/superadmin/templates/catalog/edit.blade.php`
- `resources/views/backoffice/superadmin/templates/purchases/index.blade.php`

### Implementation Prompt

```
You are a senior Laravel 11 developer working on a multi-tenant SaaS application.

CONTEXT:
- Multi-tenant with UUID primary keys
- This is the SUPERADMIN side — no tenant scoping needed for template_catalog (global table)
- template_purchases IS tenant-scoped
- All user-facing strings must be in FRENCH
- SuperAdmin controllers go in: app/Http/Controllers/SuperAdmin/
- SuperAdmin views go in: resources/views/backoffice/superadmin/
- SuperAdmin routes are in: routes/superadmin/

EXISTING MODELS:

TemplateCatalog (app/Models/Templates/TemplateCatalog.php):
- Table: template_catalog (migration columns: id UUID, code unique, name, slug unique, document_type enum, category, description, features JSON, engine enum, view_path, css_path, version, price decimal, currency char(3) default MAD, is_free boolean, is_featured boolean, is_active boolean, sort_order, created_by nullable FK, timestamps)
- Fillable: name, description, category, preview_image, price, is_premium
- NOTE: The fillable doesn't match the migration! You must update fillable to include: code, slug, document_type, features, engine, view_path, css_path, version, price, currency, is_free, is_featured, is_active, sort_order, created_by
- Relationships: hasMany(TenantTemplate), hasMany(TemplatePurchase)

TemplatePurchase (app/Models/Templates/TemplatePurchase.php):
- Table: template_purchases (migration columns: id UUID, tenant_id FK, template_id FK, amount decimal, currency char(3), status enum pending/paid/refunded/cancelled, payment_provider enum stripe/manual, provider_payment_id, approved_by nullable FK, paid_at, refunded_at, timestamps)
- Fillable: tenant_id, template_catalog_id, purchase_date, expires_at
- NOTE: Fillable doesn't match migration! Update to: tenant_id, template_id, amount, currency, status, payment_provider, provider_payment_id, approved_by, paid_at, refunded_at
- Relationships: belongsTo(Tenant), belongsTo(TemplateCatalog)

REFERENCE UI TEMPLATE for index pages: resources/views/products.blade.php (static theme template)
REFERENCE UI TEMPLATE for create/edit forms: resources/views/add-product.blade.php

TASK:

1. **Fix the TemplateCatalog model fillable** to match the actual migration columns.

2. **Fix the TemplatePurchase model fillable** to match the actual migration columns.

3. Create `app/Http/Controllers/SuperAdmin/TemplateCatalogController.php`:
   - `index(Request $request)` — List all templates with search (name, code) + filter by document_type, is_active. Paginate 15. Count tenant activations per template.
   - `create()` — Show form with document_type options (invoice, quote, delivery_challan, credit_note, debit_note, purchase_order, vendor_bill, receipt)
   - `store(Request $request)` — Validate and create. Auto-generate slug from name. Set created_by to auth user.
   - `edit(TemplateCatalog $template)` — Show edit form
   - `update(Request $request, TemplateCatalog $template)` — Validate and update
   - `destroy(TemplateCatalog $template)` — Delete if no tenants have it activated

   Validation rules:
   - name: required|string|max:255
   - code: required|string|max:50|unique:template_catalog,code (ignore on update)
   - document_type: required|in:invoice,quote,delivery_challan,credit_note,debit_note,purchase_order,vendor_bill,receipt
   - engine: required|in:blade,html,mjml
   - view_path: required|string|max:500
   - price: required|numeric|min:0
   - is_free: boolean
   - is_featured: boolean
   - is_active: boolean

4. Create `app/Http/Controllers/SuperAdmin/TemplatePurchaseController.php`:
   - `index(Request $request)` — List all purchases with tenant name, template name, amount, status. Search by tenant name. Filter by status.
   - `approve(TemplatePurchase $purchase)` — Set status to 'paid', approved_by to auth user, paid_at to now(). Auto-activate template for the tenant (create TenantTemplate record). Redirect with "Achat approuvé avec succès."

5. Update `routes/superadmin/templates.php` with resourceful routes:
   - Template catalog CRUD: sa.templates.catalog.*
   - Template purchases: sa.templates.purchases.index, sa.templates.purchases.approve

6. Create the 4 Blade views following the reference templates' exact HTML/CSS structure.

All strings in French.
```

### Expected Result

- SuperAdmin can manage the template marketplace (add, edit, deactivate templates)
- SuperAdmin can view and approve template purchases
- Approved purchases auto-activate the template for the tenant
- Tenants continue to use the existing `InvoiceTemplateSettingsController` to browse and activate free templates

---

---

# SECTION 6 — REPORTS MODULE

## Explanation

The `ReportsController` exists but only has an empty `index()` method. A detailed phase plan exists at `tasks/phases/phase-8-reports.md`. The reports module needs to provide analytical insights for:

1. **Sales Report** — Revenue, invoice status breakdown, top customers
2. **Purchases Report** — Spending by supplier, PO status
3. **Finance Report** — Income vs expenses, profit/loss, cash flow
4. **Tax Report** — Tax collected vs tax paid
5. **Accounts Receivable Aging** — Outstanding customer invoices by age
6. **Accounts Payable Aging** — Outstanding vendor bills by age

### Architecture Plan

**Files to create:**
- `app/Services/Reports/ReportService.php` — all aggregate queries
- `app/Http/Controllers/Backoffice/Reports/SalesReportController.php`
- `app/Http/Controllers/Backoffice/Reports/PurchaseReportController.php`
- `app/Http/Controllers/Backoffice/Reports/FinanceReportController.php`
- `app/Http/Controllers/Backoffice/Reports/TaxReportController.php`
- `app/Http/Controllers/Backoffice/Reports/AgingReportController.php`
- `app/Jobs/ExportReportJob.php` — CSV/Excel export via queue
- `resources/views/backoffice/reports/sales.blade.php`
- `resources/views/backoffice/reports/purchases.blade.php`
- `resources/views/backoffice/reports/finance.blade.php`
- `resources/views/backoffice/reports/tax.blade.php`
- `resources/views/backoffice/reports/aging.blade.php`

**Reports are read-only.** No FormRequests needed.

**Reference Blade templates:**
- `resources/views/sales-report.blade.php` (static theme template for sales report UI)
- `resources/views/customers-report.blade.php` (static theme template for customer report UI)
- `resources/views/admin-dashboard.blade.php` (KPI card patterns)

### Implementation Prompt

```
You are a senior Laravel 11 developer working on a multi-tenant SaaS application.

CONTEXT:
- Multi-tenant with UUID primary keys
- Tenant isolation is AUTOMATIC via BelongsToTenant trait on all models (adds global scope)
- All user-facing strings must be in FRENCH
- Reports are READ-ONLY — no create/update/delete
- Use Cache::remember() with tenant-scoped keys and 5-minute TTL
- The currency symbol comes from tenant's default_currency

EXISTING MODELS AND THEIR KEY COLUMNS:

Invoice: status (draft/sent/partial/paid/overdue/void), issue_date, due_date, total, amount_paid, amount_due, customer_id
  - InvoiceItem: product_id, quantity, unit_price, tax_rate, line_total
Quote: status (draft/sent/accepted/rejected/expired/cancelled), issue_date, total, customer_id
Payment: status (pending/succeeded/failed/refunded/cancelled), payment_date, amount, customer_id
CreditNote: status (draft/issued/applied/void), issue_date, total, customer_id
Expense: expense_date, amount, payment_status (unpaid/paid/partial), category_id, supplier_id
Income: income_date, amount, category_id, customer_id
PurchaseOrder: status (draft/sent/confirmed/partially_received/received/cancelled), order_date, total, supplier_id
VendorBill: status (draft/posted/paid/void), issue_date, due_date, total, amount_paid, amount_due, supplier_id
Supplier: name, status
Customer: name, status
BankAccount: current_balance, bank_name
Product: name, selling_price, purchase_price
ProductStock: quantity_on_hand, reorder_point, warehouse_id

REFERENCE BLADE TEMPLATES (static theme files to copy structure from):
- resources/views/sales-report.blade.php — use for Sales Report page structure
- resources/views/customers-report.blade.php — use for Aging Report page structure
- resources/views/admin-dashboard.blade.php — use for KPI cards

EXISTING PERMISSIONS (from PermissionSeeder):
- reports.sales.view, reports.purchases.view, reports.inventory.view, reports.finance.view, reports.loans.view

TASK:

1. Create `app/Services/Reports/ReportService.php` with these methods:

   a) `salesSummary(string $from, string $to): array`
      Returns:
      - total_invoices (count), total_revenue (sum of total where status != void), total_collected (sum of amount_paid), total_outstanding (sum of amount_due)
      - revenue_by_month: array of [month => revenue] for chart
      - top_customers: top 10 customers by total revenue
      - invoice_status_breakdown: count per status
      - total_quotes, quotes_accepted, quotes_conversion_rate

   b) `purchaseSummary(string $from, string $to): array`
      Returns:
      - total_purchase_orders (count), total_spending (sum of total), total_paid (sum of vendor bill amount_paid), total_outstanding_payable
      - spending_by_supplier: top 10 suppliers by spending
      - po_status_breakdown: count per status

   c) `financeSummary(string $from, string $to): array`
      Returns:
      - total_income, total_expenses, net_profit (income - expenses)
      - income_by_category, expenses_by_category
      - income_by_month, expenses_by_month (for chart)
      - bank_account_balances: all active accounts with current_balance

   d) `taxSummary(string $from, string $to): array`
      Returns:
      - total_tax_collected: sum of tax_total from invoices (status != void/draft)
      - total_tax_paid: sum of tax_total from vendor_bills (status != void/draft) + sum of tax from purchase_orders
      - net_tax_liability: collected - paid
      - tax_by_month: monthly breakdown

   e) `receivableAging(): array`
      Returns aged buckets for outstanding invoices:
      - current (0-30 days), 31-60 days, 61-90 days, 90+ days
      - Each bucket: customer_name, invoice_number, issue_date, due_date, total, amount_due, days_overdue
      - Summary totals per bucket

   f) `payableAging(): array`
      Same as receivable but for vendor_bills (outstanding)

   All methods must:
   - Use Cache::remember("report:{type}:{tenantId}:{from}:{to}", 300, ...)
   - NOT add where('tenant_id', ...) — the BelongsToTenant trait handles this automatically
   - Use selectRaw for aggregations
   - Handle null/empty results gracefully

2. Create 5 report controllers in `app/Http/Controllers/Backoffice/Reports/`:

   Each controller has:
   - `index(Request $request)` — Accept from/to date params (default: start of current month to today). Call ReportService. Return view.
   - `export(Request $request)` — Dispatch ExportReportJob. Redirect back with "L'export est en cours de préparation."

   Authorization: `abort_unless(auth()->user()->can('reports.{type}.view'), 403)`

   Controllers:
   - SalesReportController (permission: reports.sales.view)
   - PurchaseReportController (permission: reports.purchases.view)
   - FinanceReportController (permission: reports.finance.view)
   - TaxReportController (permission: reports.finance.view)
   - AgingReportController (permission: reports.finance.view)

3. Create `app/Jobs/ExportReportJob.php`:
   - Implements ShouldQueue
   - Constructor: tenantId, reportType, from, to, userId
   - handle(): Set TenantContext, call ReportService, generate CSV, store in storage/app/exports/{tenantId}/
   - Generate CSV with fputcsv()

4. Update `routes/backoffice/reports.php`:
```php
Route::prefix('reports')->as('reports.')->group(function () {
    Route::get('/', [ReportsController::class, 'index'])->name('index');

    Route::get('sales', [SalesReportController::class, 'index'])->middleware('permission:reports.sales.view')->name('sales');
    Route::post('sales/export', [SalesReportController::class, 'export'])->middleware('permission:reports.sales.view')->name('sales.export');

    Route::get('purchases', [PurchaseReportController::class, 'index'])->middleware('permission:reports.purchases.view')->name('purchases');
    Route::post('purchases/export', [PurchaseReportController::class, 'export'])->middleware('permission:reports.purchases.view')->name('purchases.export');

    Route::get('finance', [FinanceReportController::class, 'index'])->middleware('permission:reports.finance.view')->name('finance');
    Route::post('finance/export', [FinanceReportController::class, 'export'])->middleware('permission:reports.finance.view')->name('finance.export');

    Route::get('tax', [TaxReportController::class, 'index'])->middleware('permission:reports.finance.view')->name('tax');
    Route::post('tax/export', [TaxReportController::class, 'export'])->middleware('permission:reports.finance.view')->name('tax.export');

    Route::get('aging', [AgingReportController::class, 'index'])->middleware('permission:reports.finance.view')->name('aging');
    Route::post('aging/export', [AgingReportController::class, 'export'])->middleware('permission:reports.finance.view')->name('aging.export');
});
```

5. Create Blade views for each report.

   IMPORTANT: You MUST base each view on the static reference templates:
   - Sales report: copy structure from resources/views/sales-report.blade.php
   - Aging report: copy structure from resources/views/customers-report.blade.php
   - All others: use the same card/table patterns

   Each report page must have:
   - Date range filter (from + to date inputs) in the card-header
   - KPI summary cards at the top (4 cards in a row, col-xl-3 col-lg-6)
   - Data table with the detail rows
   - Export button (POST form)
   - @extends('backoffice.layout.mainlayout')
   - isax icons, badge-soft-* status classes

6. Update the Reports index page to show cards linking to each report type.

7. Update the sidebar (resources/views/backoffice/layout/partials/sidebar.blade.php) to add sub-items under "Rapports" linking to each report.

All strings in French. Use number_format($value, 2, ',', ' ') for monetary values.
```

### Expected Result

- 5 analytical report pages with date range filtering
- KPI summary cards showing totals
- Data tables with detail breakdowns
- CSV export via background jobs
- Cached queries for performance (5-minute TTL)
- Sidebar navigation to each report

---

---

# SECTION 7 — MISSING SERVICES

## Explanation

The project uses a service layer pattern for complex business logic (InvoiceService, QuoteService, PaymentService, CreditNoteService, StockService). Several domains are missing their service classes, meaning business logic is either in controllers (too fat) or doesn't exist yet.

### Architecture Plan

| Service | Domain | Purpose |
|---------|--------|---------|
| `DeliveryChallanService` | Sales | Create/update challans with items and charges, status transitions |
| `RefundService` | Sales | Process refunds, update payment totals, reverse bank balance |
| `PurchaseOrderService` | Purchases | Create/update POs with items, receive goods, cancel |
| `VendorBillService` | Purchases | Create/update bills, link to POs/GRNs, payment tracking |
| `DebitNoteService` | Purchases | Create/update debit notes with items, apply to vendor bills |
| `SupplierPaymentService` | Purchases | Record payments, allocate to vendor bills, update balances |
| `ExpenseService` | Finance | Create/update expenses with bank balance management |
| `IncomeService` | Finance | Create/update incomes with bank balance management |
| `LoanService` | Finance | Already covered in Section 3 |
| `ReportService` | Reports | Already covered in Section 6 |

### Implementation Prompt

```
You are a senior Laravel 11 developer working on a multi-tenant SaaS application.

CONTEXT:
- Multi-tenant with UUID primary keys
- Tenant isolation via BelongsToTenant trait (automatic global scope)
- Service layer pattern — services are injected into controllers
- Services use DB::transaction() for multi-model operations
- Services throw \DomainException with French messages for business rule violations
- DocumentNumberService generates sequential document numbers: app(DocumentNumberService::class)->next('type')
- TaxCalculationService handles tax math: $this->taxService->calculateDocument($items, $charges)
- All financial operations that touch bank_accounts.current_balance must use lockForUpdate()
- Services are registered as singletons in AppServiceProvider

EXISTING SERVICE PATTERN (from InvoiceService):
```php
class InvoiceService
{
    public function __construct(
        private readonly TaxCalculationService $taxService,
        private readonly DocumentNumberService $docService,
    ) {}

    public function create(array $validated): Invoice
    {
        return DB::transaction(function () use ($validated) {
            $items = $validated['items'] ?? [];
            $charges = $validated['charges'] ?? [];
            $totals = $this->taxService->calculateDocument($items, $charges);

            $invoice = Invoice::create([...]);

            foreach ($totals['calculated_items'] as $item) {
                InvoiceItem::create([...]);
            }

            return $invoice->load('items', 'charges');
        });
    }

    public function update(Invoice $invoice, array $validated): Invoice
    {
        if ($invoice->status !== 'draft') {
            throw new \DomainException('Seules les factures en brouillon peuvent être modifiées.');
        }
        return DB::transaction(function () use ($invoice, $validated) {
            // recalculate totals, delete old items, create new items
        });
    }

    public function transition(Invoice $invoice, string $newStatus): void
    {
        $allowed = ['draft' => ['sent', 'void'], ...];
        // validate transition, update status
    }

    public function updatePaymentTotals(Invoice $invoice): void
    {
        // recalculate amount_paid/amount_due from allocations
        // auto-transition to paid/partial
    }
}
```

TASK:
Create the following service classes. Each must follow the exact same patterns as InvoiceService.

1. `app/Services/Sales/DeliveryChallanService.php`:
   - `create(array $validated): DeliveryChallan` — Create with items + charges, auto-generate number via DocumentNumberService('delivery_challan'), calculate totals via TaxCalculationService
   - `update(DeliveryChallan $challan, array $validated): DeliveryChallan` — Only draft. Recalculate totals, sync items/charges
   - `transition(DeliveryChallan $challan, string $newStatus): void` — Allowed: draft→issued, issued→delivered, draft→cancelled, issued→cancelled

2. `app/Services/Sales/RefundService.php`:
   - `create(array $validated): Refund` — Create refund linked to payment. Validate: amount <= payment's remaining refundable amount. Status: pending.
   - `process(Refund $refund): void` — Set status to succeeded, set refunded_at. Update parent Payment: if fully refunded set status=refunded. Optionally increment bank account balance (reverse the payment).
   - `fail(Refund $refund, string $reason): void` — Set status to failed.

3. `app/Services/Purchases/PurchaseOrderService.php`:
   - `create(array $validated): PurchaseOrder` — Create PO with items, auto-generate number, calculate totals. Status: draft.
   - `update(PurchaseOrder $po, array $validated): PurchaseOrder` — Only draft. Sync items, recalculate.
   - `transition(PurchaseOrder $po, string $newStatus): void` — Allowed: draft→sent, sent→confirmed, confirmed→partially_received, partially_received→received, confirmed→received, draft→cancelled, sent→cancelled
   - `receive(PurchaseOrder $po, array $receivedItems): GoodsReceipt` — Create GoodsReceipt + items, update PO item received_quantity, auto-transition PO to partially_received or received. Use StockService to add stock.

4. `app/Services/Purchases/VendorBillService.php`:
   - `create(array $validated): VendorBill` — Create bill, auto-generate number, set amount_due = total. Link to PO/GRN if provided.
   - `update(VendorBill $bill, array $validated): VendorBill` — Only draft.
   - `transition(VendorBill $bill, string $newStatus): void` — Allowed: draft→posted, posted→paid, draft→void, posted→void
   - `updatePaymentTotals(VendorBill $bill): void` — Recalculate amount_paid/amount_due from SupplierPaymentAllocations + DebitNoteApplications. Auto-transition to paid.

5. `app/Services/Purchases/DebitNoteService.php`:
   - `create(array $validated): DebitNote` — Create with items, auto-generate number, calculate totals.
   - `update(DebitNote $note, array $validated): DebitNote` — Only draft.
   - `apply(DebitNote $note, array $allocations): void` — Apply debit note to vendor bills. Create DebitNoteApplication records. Update vendor bill amount_paid/amount_due.
   - `transition(DebitNote $note, string $newStatus): void`

6. `app/Services/Purchases/SupplierPaymentService.php`:
   - `create(array $validated): SupplierPayment` — Create payment, allocate to vendor bills (create SupplierPaymentAllocation records), update each vendor bill's amount_paid/amount_due, decrement bank account balance.
   - `delete(SupplierPayment $payment): void` — Reverse: restore vendor bill amounts, increment bank account balance, delete allocations, delete payment.

7. `app/Services/Finance/ExpenseService.php`:
   - `create(array $validated): Expense` — Create expense, auto-generate number. If payment_status=paid and bank_account_id set: decrement bank balance.
   - `update(Expense $expense, array $validated): Expense` — Reverse old bank impact, apply new.
   - `delete(Expense $expense): void` — Reverse bank impact, delete.

8. `app/Services/Finance/IncomeService.php`:
   - `create(array $validated): Income` — Create income, auto-generate number. If bank_account_id set: increment bank balance.
   - `update(Income $income, array $validated): Income` — Reverse old, apply new.
   - `delete(Income $income): void` — Reverse, delete.

For each service:
- Use constructor injection for dependencies (TaxCalculationService, DocumentNumberService, StockService where needed)
- Wrap all multi-model operations in DB::transaction()
- Use lockForUpdate() on BankAccount when modifying current_balance
- Throw \DomainException with French messages for invalid operations
- Return fresh model with relationships loaded after create/update

Register all services in AppServiceProvider::register() as singletons.
```

### Expected Result

- Clean separation of business logic from controllers
- Controllers become thin — just validate, delegate to service, redirect
- All financial operations are transactional and race-condition safe
- Consistent error handling with French domain exceptions
- Services can be reused across controllers (e.g., VendorBillService used by both VendorBillController and PurchaseOrderController)

---

---

# SECTION 8 — HIGH VALUE SAAS FEATURES

## Explanation

These are features that transform the application from a basic CRUD tool into a production-ready SaaS product. Each is independent and can be implemented in any order.

---

## 8A — PDF Generation (DomPDF)

### Architecture Plan

Generate professional PDF documents for invoices, quotes, credit notes, delivery challans, purchase orders, and vendor bills.

**Files to create:**
- `app/Services/Pdf/PdfGeneratorService.php`
- PDF Blade templates in `resources/views/pdf/`
- Download/stream endpoints on each document controller

### Implementation Prompt

```
You are a senior Laravel 11 developer working on a multi-tenant SaaS application.

CONTEXT:
- Multi-tenant with UUID primary keys
- The barryvdh/laravel-dompdf package is available (or install it)
- Template system exists: tenant can have activated templates (tenant_templates table)
- Document model exists for storing generated PDFs (app/Models/System/Document.php)
- All user-facing strings in FRENCH
- Currency formatting: number_format($value, 2, ',', ' ')

TASK:

1. Install DomPDF: composer require barryvdh/laravel-dompdf

2. Create `app/Services/Pdf/PdfGeneratorService.php`:
   ```php
   class PdfGeneratorService
   {
       public function generateInvoice(Invoice $invoice): string  // returns file path
       public function generateQuote(Quote $quote): string
       public function generateCreditNote(CreditNote $note): string
       public function generateDeliveryChallan(DeliveryChallan $challan): string
       public function generatePurchaseOrder(PurchaseOrder $po): string
       public function generateVendorBill(VendorBill $bill): string
       public function stream(string $type, Model $document): Response  // stream PDF to browser
   }
   ```

   Each method must:
   - Load the document with all relationships (items, charges, customer/supplier, tenant settings)
   - Get the active template for this document type from tenant_templates
   - Fall back to default template if no custom template activated
   - Generate PDF via Pdf::loadView() with paper size A4
   - Store the PDF file in storage/app/documents/{tenantId}/
   - Create a Document record in the database
   - Return the stored file path

3. Create default PDF Blade templates:
   - `resources/views/pdf/invoice.blade.php`
   - `resources/views/pdf/quote.blade.php`
   - `resources/views/pdf/credit-note.blade.php`
   - `resources/views/pdf/delivery-challan.blade.php`
   - `resources/views/pdf/purchase-order.blade.php`
   - `resources/views/pdf/vendor-bill.blade.php`

   Each template must:
   - Be a standalone HTML page (no @extends — PDF templates are self-contained)
   - Include company logo and details from tenant settings (bill_from_snapshot or company_settings)
   - Include customer/supplier details
   - Include line items table with: description, quantity, unit price, discount, tax, total
   - Include totals section: subtotal, discount, tax, total
   - Include notes and terms
   - Include payment details / bank info
   - Use inline CSS (DomPDF doesn't support external stylesheets well)
   - French labels: "Facture", "Devis", "Avoir", "Bon de livraison", "Bon de commande", "Facture fournisseur"
   - Professional styling with the company's branding

4. Add download/stream routes and controller methods:
   - Add to InvoiceController: `pdf(Invoice $invoice)` — stream PDF
   - Add to QuoteController: `pdf(Quote $quote)` — stream PDF
   - Same for CreditNote, DeliveryChallan, PurchaseOrder, VendorBill controllers
   - Routes: GET /{document}/{id}/pdf → controller::pdf → bo.{domain}.{type}.pdf

5. Add a "Télécharger PDF" button on each document's show page.

Register PdfGeneratorService as singleton in AppServiceProvider.
```

### Expected Result

Users can generate and download professional PDF documents for all business documents. PDFs include company branding, line items, totals, and terms.

---

## 8B — Email Sending (Invoices & Quotes)

### Implementation Prompt

```
You are a senior Laravel 11 developer working on a multi-tenant SaaS application.

CONTEXT:
- Multi-tenant with UUID primary keys
- PdfGeneratorService exists (from 8A) for generating PDF attachments
- EmailLog model exists at app/Models/System/EmailLog.php
- email_logs table: tenant_id, recipient_email, email_subject, email_body, status (queued/sent/failed), sent_at, error_message
- All user-facing strings in FRENCH

TASK:

1. Create `app/Mail/DocumentMail.php` (Mailable):
   - Constructor: $document (Invoice|Quote|CreditNote), $pdfPath, $tenant
   - Subject: dynamic based on document type ("Facture N° {number}", "Devis N° {number}", etc.)
   - Attach the PDF file
   - Use a Blade email template

2. Create email Blade template `resources/views/emails/document-sent.blade.php`:
   - Professional email body in French
   - Include document type, number, date, total amount
   - Include company name from tenant settings
   - Call-to-action text (not a button — simple text email)

3. Create `app/Jobs/SendDocumentEmailJob.php` (ShouldQueue):
   - Constructor: documentType, documentId, tenantId, recipientEmail
   - handle(): Set TenantContext, load document, generate PDF, send mail, log in email_logs
   - On success: create EmailLog with status=sent
   - On failure: create EmailLog with status=failed and error_message

4. Add `sendEmail(Request $request)` method to InvoiceController and QuoteController:
   - Validate: email (required|email)
   - Dispatch SendDocumentEmailJob
   - For invoices: also transition status to 'sent' if currently 'draft'
   - Redirect back with "Le document a été envoyé par email à {email}."

5. Add routes:
   - POST /sales/invoices/{invoice}/send-email → sendEmail → bo.sales.invoices.send-email
   - POST /sales/quotes/{quote}/send-email → sendEmail → bo.sales.quotes.send-email

6. Add "Envoyer par email" button on invoice and quote show pages.
   - Button opens a modal with email input (pre-filled from customer email)
   - Submit dispatches the job

All strings in French.
```

### Expected Result

Users can send invoices and quotes as PDF attachments via email. Emails are queued for async delivery. All sends are logged in the email_logs table.

---

## 8C — Dashboard Widgets

### Implementation Prompt

```
You are a senior Laravel 11 developer working on a multi-tenant SaaS application.

CONTEXT:
- Multi-tenant with UUID primary keys
- Tenant isolation automatic via BelongsToTenant trait
- Dashboard view exists: resources/views/backoffice/index.blade.php
- Dashboard controller: app/Http/Controllers/Backoffice/DashboardController.php
- Reference static template: resources/views/admin-dashboard.blade.php
- All user-facing strings in FRENCH
- Currency: tenant default_currency

TASK:

Update DashboardController::index() to pass dashboard data and update the Blade view.

1. Add these queries to the index() method:

   KPI Cards (top row, 4 cards):
   - total_revenue_month: sum of invoice totals this month (status != void)
   - total_expenses_month: sum of expense amounts this month
   - total_outstanding: sum of invoice amount_due where amount_due > 0
   - total_customers: count of active customers

   Charts:
   - revenue_chart: monthly revenue for last 12 months (array of [month_label => total])
   - expense_chart: monthly expenses for last 12 months

   Recent Items:
   - recent_invoices: 5 latest invoices with customer name
   - recent_payments: 5 latest payments with customer name
   - overdue_invoices: invoices where due_date < today AND status in (sent, partial, overdue), limit 10

   Quick Stats:
   - invoices_this_month: count
   - quotes_pending: count where status = sent
   - low_stock_products: count where quantity_on_hand <= reorder_point (via ProductStock)

2. Use Cache::remember() with 5-minute TTL for all queries. Key format: "dashboard:{tenantId}:{metric}"

3. Update the dashboard Blade view following the EXACT structure from resources/views/admin-dashboard.blade.php:
   - 4 KPI cards in a row (col-xl-3 col-lg-6)
   - Revenue chart (use the theme's built-in chart JS, same as reference)
   - Recent invoices table
   - Overdue invoices table
   - Keep all CSS classes from the reference template

All strings in French. Monetary values: number_format($value, 2, ',', ' ') . ' ' . $currency
```

### Expected Result

A rich, data-driven dashboard showing KPI cards, revenue charts, recent invoices, overdue alerts, and quick stats.

---

## 8D — Export to Excel/CSV

### Implementation Prompt

```
You are a senior Laravel 11 developer working on a multi-tenant SaaS application.

CONTEXT:
- Multi-tenant with UUID primary keys
- All user-facing strings in FRENCH
- Models use BelongsToTenant (automatic scoping)

TASK:

1. Install Laravel Excel: composer require maatwebsite/excel

2. Create export classes in `app/Exports/`:

   a) `InvoiceExport.php` — Implements FromQuery, WithHeadings, WithMapping
      - Columns: Numéro, Client, Date, Échéance, Sous-total, Taxe, Total, Payé, Reste, Statut
      - Accept date range filter

   b) `CustomerExport.php` — Same pattern
      - Columns: Nom, Email, Téléphone, Type, Statut

   c) `ExpenseExport.php`
      - Columns: Numéro, Date, Montant, Mode de paiement, Statut, Catégorie, Fournisseur

   d) `ProductExport.php`
      - Columns: Code, Nom, Type, Prix de vente, Prix d'achat, Quantité, Statut

   e) `SupplierExport.php`
      - Columns: Nom, Email, Téléphone, Statut

3. Add export routes and controller methods:
   For each domain, add a GET export endpoint:
   - GET /sales/invoices/export → InvoiceController::export
   - GET /crm/customers/export → CustomerController::export
   - GET /finance/expenses/export → ExpenseController::export
   - GET /catalog/products/export → ProductController::export
   - GET /purchases/suppliers/export → SupplierController::export

   Each export method:
   - Accept same filters as the index method
   - Return Excel::download(new XExport($filters), 'filename.xlsx')

4. Add "Exporter" button on each index page (next to the search bar).

All column headings in French.
```

### Expected Result

Users can export any list to Excel with the same filters applied. The export respects tenant isolation automatically.

---

## 8E — Bulk Actions

### Implementation Prompt

```
You are a senior Laravel 11 developer working on a multi-tenant SaaS application.

CONTEXT:
- Multi-tenant with UUID primary keys
- All user-facing strings in FRENCH
- List pages use table with checkboxes (reference: resources/views/invoices.blade.php has checkbox column)

TASK:

1. Add bulk action support to these controllers:
   - InvoiceController: bulk delete (draft only), bulk send, bulk void
   - QuoteController: bulk delete (draft only)
   - CustomerController: bulk activate, bulk deactivate, bulk delete
   - ProductController: bulk activate, bulk deactivate, bulk delete
   - ExpenseController: bulk delete

2. For each controller, add a `bulkAction(Request $request)` method:
   ```php
   public function bulkAction(Request $request)
   {
       $request->validate([
           'action' => 'required|in:delete,send,void,activate,deactivate',
           'ids' => 'required|array|min:1',
           'ids.*' => 'uuid',
       ]);

       $models = Model::whereIn('id', $request->ids)->get();

       // Verify authorization for each
       foreach ($models as $model) {
           $this->authorize('delete', $model); // or appropriate permission
       }

       // Execute action
       match ($request->action) {
           'delete' => $models->each->delete(),
           'send' => $models->each(fn($m) => $service->transition($m, 'sent')),
           // etc.
       };

       return redirect()->back()->with('success', count($models) . ' éléments mis à jour.');
   }
   ```

3. Add POST routes for bulk actions:
   - POST /sales/invoices/bulk → bulkAction → bo.sales.invoices.bulk
   - Same pattern for others

4. Add JavaScript to index pages for:
   - "Select all" checkbox in table header
   - Bulk action dropdown that appears when items are selected
   - Form submission with selected IDs

Keep the existing CSS/JS patterns from the theme.
All strings in French.
```

### Expected Result

Users can select multiple records and perform batch operations (delete, status change, send) in one action.

---

## 8F — Global Search

### Implementation Prompt

```
You are a senior Laravel 11 developer working on a multi-tenant SaaS application.

CONTEXT:
- Multi-tenant with UUID primary keys
- All user-facing strings in FRENCH
- Header already has a search input (resources/views/backoffice/layout/partials/header.blade.php)
- Models use BelongsToTenant (automatic scoping)

TASK:

1. Create `app/Http/Controllers/Backoffice/SearchController.php`:
   ```php
   public function search(Request $request)
   {
       $q = $request->input('q', '');
       if (strlen($q) < 2) return response()->json([]);

       $results = collect();

       // Search invoices
       $results = $results->merge(
           Invoice::where('number', 'like', "%{$q}%")
               ->orWhereHas('customer', fn($c) => $c->where('name', 'like', "%{$q}%"))
               ->limit(5)->get()
               ->map(fn($i) => ['type' => 'Facture', 'label' => "{$i->number} — {$i->customer->name}", 'url' => route('bo.sales.invoices.show', $i)])
       );

       // Search customers
       $results = $results->merge(
           Customer::where('name', 'like', "%{$q}%")
               ->orWhere('email', 'like', "%{$q}%")
               ->limit(5)->get()
               ->map(fn($c) => ['type' => 'Client', 'label' => $c->name, 'url' => route('bo.crm.customers.show', $c)])
       );

       // Search products, suppliers, quotes, payments
       // Same pattern for each

       return response()->json($results->take(20));
   }
   ```

2. Add route: GET /search → SearchController::search → bo.search (returns JSON)

3. Add JavaScript to the header search input:
   - Debounced AJAX call (300ms) to /search?q=...
   - Display dropdown with results grouped by type
   - Each result is a clickable link
   - Use the existing search input in the header partial
   - Style the dropdown to match the theme

All type labels in French (Facture, Client, Produit, Fournisseur, Devis, Paiement).
```

### Expected Result

Users can search across all entities from the header. Results appear in a dropdown grouped by type, with direct links to each record.

---

## 8G — Inventory Alerts

### Implementation Prompt

```
You are a senior Laravel 11 developer working on a multi-tenant SaaS application.

CONTEXT:
- Multi-tenant with UUID primary keys
- ProductStock model: quantity_on_hand, reorder_point, warehouse_id, product_id
- All user-facing strings in FRENCH
- Laravel notifications system available

TASK:

1. Create `app/Console/Commands/CheckLowStockCommand.php`:
   - Runs for all active tenants
   - For each tenant: query ProductStock where quantity_on_hand <= reorder_point AND reorder_point > 0
   - If low stock items found: notify tenant admin users
   - Log results

2. Create `app/Notifications/LowStockNotification.php`:
   - Channels: mail, database
   - Mail: French subject "Alerte stock bas — {count} produits"
   - Lists products with current stock vs reorder point
   - Toasts: French message

3. Schedule the command in `routes/console.php`:
   ```php
   Schedule::command('inventory:check-low-stock')->dailyAt('08:00');
   ```

4. Add a bell icon notification badge in the header partial showing unread notification count.

All strings in French.
```

### Expected Result

Tenant admins receive daily notifications when products fall below their reorder point. Notifications appear both as emails and in-app alerts.

---

## 8H — Duplicate Document

### Implementation Prompt

```
You are a senior Laravel 11 developer working on a multi-tenant SaaS application.

CONTEXT:
- Multi-tenant with UUID primary keys
- Documents have line items (InvoiceItem, QuoteItem, etc.) and charges (InvoiceCharge, QuoteCharge, etc.)
- DocumentNumberService generates new document numbers
- All user-facing strings in FRENCH

TASK:

Add a "Dupliquer" action to Invoice, Quote, and PurchaseOrder controllers.

1. Add `duplicate()` method to each controller:

   InvoiceController::duplicate(Invoice $invoice):
   ```php
   public function duplicate(Invoice $invoice)
   {
       $this->authorize('create', Invoice::class);

       $newInvoice = DB::transaction(function () use ($invoice) {
           $new = $invoice->replicate(['id', 'number', 'status', 'sent_at', 'paid_at', 'amount_paid', 'created_at', 'updated_at', 'deleted_at']);
           $new->number = app(DocumentNumberService::class)->next('invoice');
           $new->status = 'draft';
           $new->amount_paid = 0;
           $new->amount_due = $invoice->total;
           $new->issue_date = now();
           $new->save();

           foreach ($invoice->items as $item) {
               $newItem = $item->replicate(['id', 'invoice_id']);
               $newItem->invoice_id = $new->id;
               $newItem->save();
           }

           foreach ($invoice->charges as $charge) {
               $newCharge = $charge->replicate(['id', 'invoice_id']);
               $newCharge->invoice_id = $new->id;
               $newCharge->save();
           }

           return $new;
       });

       return redirect()->route('bo.sales.invoices.edit', $newInvoice)
           ->with('success', 'Facture dupliquée avec succès.');
   }
   ```

2. Same pattern for QuoteController::duplicate and PurchaseOrderController::duplicate.

3. Add routes:
   - POST /sales/invoices/{invoice}/duplicate → duplicate → bo.sales.invoices.duplicate
   - POST /sales/quotes/{quote}/duplicate → duplicate → bo.sales.quotes.duplicate
   - POST /purchases/purchase-orders/{purchaseOrder}/duplicate → duplicate → bo.purchases.purchase-orders.duplicate

4. Add "Dupliquer" option in the action dropdown on show pages and index pages.

All strings in French.
```

### Expected Result

Users can clone any invoice, quote, or purchase order with one click. The duplicate gets a new number, draft status, today's date, and all line items copied.

---

---

# APPENDIX — ARCHITECTURAL REFERENCE

## File Naming Conventions

| Type | Pattern | Example |
|------|---------|---------|
| Controller | `app/Http/Controllers/Backoffice/{Domain}/{Model}Controller.php` | `InvoiceController.php` |
| Service | `app/Services/{Domain}/{Model}Service.php` | `InvoiceService.php` |
| Policy | `app/Policies/{Model}Policy.php` | `InvoicePolicy.php` |
| Store Request | `app/Http/Requests/{Domain}/Store/Store{Model}Request.php` | `StoreInvoiceRequest.php` |
| Update Request | `app/Http/Requests/{Domain}/Update/Update{Model}Request.php` | `UpdateInvoiceRequest.php` |
| Model | `app/Models/{Domain}/{Model}.php` | `Invoice.php` |
| Routes | `routes/backoffice/{domain}.php` | `sales.php` |
| Views | `resources/views/backoffice/{domain}/{model}/` | `sales/invoices/` |

## Key Traits

| Trait | Purpose | Location |
|-------|---------|----------|
| `BelongsToTenant` | Adds tenant_id scoping + auto-fill on create | `app/Traits/BelongsToTenant.php` |
| `UsesTenantCurrency` | Provides virtual `$model->currency` from tenant settings | `app/Traits/UsesTenantCurrency.php` |
| `HasUuids` | UUID primary keys | Laravel built-in |

## Permission Pattern

Format: `{domain}.{module}.{action}`
Actions: `view`, `create`, `edit`, `delete`
Example: `sales.invoices.view`, `finance.expenses.create`

## Route Naming Pattern

Format: `bo.{domain}.{model}.{action}`
Example: `bo.sales.invoices.index`, `bo.finance.expenses.store`

## French Flash Messages

| Action | Message |
|--------|---------|
| Create | `{Entity} créé(e) avec succès.` |
| Update | `{Entity} mis(e) à jour avec succès.` |
| Delete | `{Entity} supprimé(e) avec succès.` |
| Send | `{Entity} envoyé(e) avec succès.` |
| Error | `Impossible de {action}. {reason}` |

## TenantContext Usage

```php
use App\Services\Tenancy\TenantContext;

TenantContext::id()    // Get current tenant UUID
TenantContext::get()   // Get current Tenant model
TenantContext::check() // Returns bool — is tenant set?
TenantContext::set($tenant) // Set context (used in jobs/commands)
```

## Registering New Services

In `app/Providers/AppServiceProvider.php`:
```php
public function register(): void
{
    $this->app->singleton(NewService::class);
}
```

## Registering New Policies

In `app/Providers/AppServiceProvider.php`:
```php
public function boot(): void
{
    Gate::policy(NewModel::class, NewModelPolicy::class);
}
```
