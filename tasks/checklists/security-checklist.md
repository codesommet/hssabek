# Security Checklist

> Run this checklist before any phase ships to production.
> Every item that is NOT checked is a security vulnerability.

---

## MULTI-TENANT DATA ISOLATION

- [ ] `tenant_id` is NOT in `$fillable` on ANY model in `app/Models/`
  ```bash
  grep -r "'tenant_id'" app/Models/ --include="*.php" | grep "fillable"
  # Expected output: EMPTY (no results)
  ```

- [ ] `BelongsToTenant` trait is applied to every tenant-owned model
  ```bash
  grep -rL "BelongsToTenant" app/Models/CRM/ app/Models/Sales/ app/Models/Catalog/ \
    app/Models/Inventory/ app/Models/Purchases/ app/Models/Finance/ --include="*.php"
  # Expected output: EMPTY (all models use the trait)
  ```

- [ ] `withoutGlobalScopes()` is NEVER called in any Controller or Service
  ```bash
  grep -r "withoutGlobalScopes" app/Http/Controllers/ app/Services/ --include="*.php"
  # Expected output: EMPTY
  ```

- [ ] Cache keys always include `tenant_id`:
  ```bash
  grep -r "Cache::remember" app/ --include="*.php" | grep -v "tenant"
  # Expected output: EMPTY (all cache calls include tenant identifier)
  ```

- [ ] TenantContext is set at the start of every queued Job that touches tenant models:
  ```bash
  grep -r "class.*implements ShouldQueue" app/Jobs/ --include="*.php" -l
  # For each file found, verify TenantContext::set() is in handle()
  ```

- [ ] Report cache keys include `tenantId` (never shared across tenants):
  ```
  Correct:   Cache::remember("report:sales:{$tenantId}:{$from}:{$to}", ...)
  Incorrect: Cache::remember("report:sales:{$from}:{$to}", ...)
  ```

---

## AUTHORIZATION

- [ ] Every Controller action has `abort_unless(auth()->user()->can('...'), 403)` as first line
  ```bash
  grep -rn "public function" app/Http/Controllers/Backoffice/ --include="*.php" | wc -l
  grep -rn "abort_unless" app/Http/Controllers/Backoffice/ --include="*.php" | wc -l
  # Both numbers should be equal (or close)
  ```

- [ ] No Controller action returns data without checking authentication
  - `auth` middleware is applied to all backoffice protected routes in `routes/web.php`

- [ ] Route model binding is NOT bypassed (never manually `find()` without TenantScope):
  ```
  WRONG: $invoice = Invoice::withoutGlobalScopes()->find($id);
  RIGHT: $invoice = Invoice::findOrFail($id); // TenantScope applied
  RIGHT: Use route model binding ({invoice} in route definition)
  ```

- [ ] Policy methods check BOTH the Spatie permission AND the tenant_id ownership:
  ```php
  // WRONG — checks permission but not ownership
  public function update(User $user, Invoice $invoice): bool {
      return $user->can('sales.invoices.edit');
  }

  // CORRECT — checks both
  public function update(User $user, Invoice $invoice): bool {
      return $user->can('sales.invoices.edit')
          && $invoice->tenant_id === $user->tenant_id;
  }
  ```

- [ ] SuperAdmin routes verified with:
  ```bash
  php artisan route:list | grep admin
  # All /admin/* routes should show 'isSuperAdmin' middleware
  ```

---

## MASS ASSIGNMENT PROTECTION

- [ ] `StoreInvoiceRequest` does NOT include: `subtotal`, `tax_amount`, `total_amount`, `invoice_number`, `status`
- [ ] `StorePaymentRequest` does NOT include: `invoice_id` as a writable field (allocation is a separate step)
- [ ] `UpdateCustomerRequest` does NOT include: `credit_used` (system-calculated)
- [ ] `StorePurchaseOrderRequest` does NOT include: `po_number`, `subtotal`, `tax_amount`, `total_amount`
- [ ] Every `Rule::exists()` for cross-model references includes tenant scope:
  ```php
  // WRONG
  Rule::exists('customers', 'id')

  // CORRECT
  Rule::exists('customers', 'id')->where('tenant_id', TenantContext::id())
  ```

---

## INPUT VALIDATION

- [ ] All date inputs validated with `'date'` or `'date_format:Y-m-d'` rule
- [ ] All amount inputs validated with `'numeric', 'min:0'`
- [ ] All UUID inputs validated with `'uuid'` rule
- [ ] String inputs have `'max:255'` (or appropriate) limit to prevent oversized payloads
- [ ] `'email'` rule applied to all email fields
- [ ] File upload fields: `'mimes:...'` + `'max:2048'` (or appropriate KB limit)
- [ ] No raw SQL string interpolation:
  ```bash
  grep -r "whereRaw\|selectRaw\|DB::statement" app/ --include="*.php"
  # For each result: verify no user input is interpolated directly
  # WRONG: ->whereRaw("name = '{$request->name}'")
  # CORRECT: ->whereRaw("name = ?", [$request->name])
  ```

---

## AUTHENTICATION

- [ ] Login rate limiting: `throttle:10,1` on login routes
- [ ] Password hashing: `bcrypt()` used everywhere (never MD5, SHA1, or plaintext)
- [ ] Password reset tokens expire after 60 minutes (check `config/auth.php`)
- [ ] Email verification enforced for new registrations (`MustVerifyEmail` on User model)
- [ ] Session invalidated on logout (`auth()->logout()` + `$request->session()->invalidate()`)
- [ ] `LoginLog` record created on every successful login

---

## FILE / MEDIA SECURITY

- [ ] MediaLibrary `UuidPathGenerator` prefixes paths with `tenant_id`:
  ```
  Path format: {tenant_id}/{uuid}/{filename}
  ```
- [ ] Private files served through authenticated controller (not directly via `public/storage`)
- [ ] Allowed MIME types restricted in `registerMediaCollections()`
- [ ] Maximum file size enforced in both FormRequest and MediaLibrary collection

---

## SENSITIVE DATA

- [ ] No hardcoded credentials in any committed file:
  ```bash
  grep -r "password\|secret\|api_key" database/seeders/ --include="*.php" | grep -v "env("
  # Expected: only env() calls, no hardcoded values
  ```
- [ ] `.env` file in `.gitignore` (it is by default in Laravel — verify it's still there)
- [ ] `.env.example` does NOT contain real values (only placeholder descriptions)
- [ ] Real emails are not in seeders:
  ```bash
  grep -r "@" database/seeders/ --include="*.php" | grep -v ".local\|example.com\|demo\|test"
  # Expected: no real-looking email addresses
  ```

---

## INFRASTRUCTURE (Pre-Production)

- [ ] `APP_DEBUG=false` in production `.env`
- [ ] `APP_ENV=production` in production `.env`
- [ ] CORS policy configured if API routes are exposed
- [ ] HTTPS enforced (redirect HTTP → HTTPS at web server level)
- [ ] Session cookie: `SESSION_SECURE_COOKIE=true` in production
- [ ] Storage link created: `php artisan storage:link`
- [ ] Queue worker running (e.g., Supervisor): `php artisan queue:work --sleep=3 --tries=3`
- [ ] Failed job table exists: `php artisan queue:failed-table && php artisan migrate`
- [ ] Log rotation configured (prevent disk fill)
- [ ] Telescope / Debugbar disabled in production

---

## AUTOMATED SECURITY GATES (run before every deploy)

```bash
# Static analysis
./vendor/bin/phpstan analyse --level=5

# Code style
./vendor/bin/pint --test

# Tests (all must pass)
php artisan test --parallel

# Check for common vulnerabilities in dependencies
composer audit
```
