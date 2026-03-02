# Module Checklist

> Copy this checklist for every new module before starting. Check off each item before marking the phase complete.

---

## PRE-BUILD (Do Before Writing Any Code)

- [ ] Read the phase file for this module in `tasks/phases/`
- [ ] Identify the exact reference Blade template to mirror (check `UI_UX_TEMPLATE_REFERENCE.md`)
- [ ] Confirm Phase 0 is complete (tenant_id removed from fillable, SoftDeletes added)
- [ ] Confirm `DocumentNumberService` is implemented (if this module generates document numbers)
- [ ] Identify all permissions this module uses (format: `group.module.action`)
- [ ] Confirm those permissions exist in `PermissionSeeder`

---

## ROUTES

- [ ] Route file is filled (`routes/backoffice/{module}.php`)
- [ ] All route names follow `bo.{domain}.{model}.{action}` convention
- [ ] Custom actions (send, cancel, convert, download) have dedicated named routes
- [ ] Routes included in `routes/web.php` inside the `auth` middleware group
- [ ] `php artisan route:list | grep {module}` shows all expected routes

---

## CONTROLLER

- [ ] Controller extends `App\Http\Controllers\Controller`
- [ ] Constructor injects required Service(s) as `readonly` properties
- [ ] Every action has `abort_unless(auth()->user()->can('...'), 403)` as first line
- [ ] `index()` uses `->paginate(15)->withQueryString()` (never `->get()` or `->all()`)
- [ ] `index()` has `->when($request->search, ...)` filter
- [ ] `store()` and `update()` use `FormRequest` (not `$request->validate()`)
- [ ] `store()` and `update()` call Service method (if complex) or `Model::create()` (if simple)
- [ ] `destroy()` calls `->delete()` (SoftDelete) — not `->forceDelete()`
- [ ] Success redirects use French flash messages: `->with('success', 'Message en français.')`
- [ ] No `response()->json()` anywhere in a Backoffice controller
- [ ] No raw DB queries — use Eloquent
- [ ] No business logic in controller — delegated to Service

---

## FORM REQUESTS

- [ ] `StoreXxxRequest` and `UpdateXxxRequest` both exist
- [ ] `authorize()` returns `true` (authorization is in controller via `abort_unless`)
- [ ] `tenant_id` is NOT in `rules()` for any field
- [ ] Unique rules include tenant scope: `Rule::unique('table', 'column')->where('tenant_id', TenantContext::id())`
- [ ] All `messages()` return French strings
- [ ] Financial total fields (subtotal, tax_amount, total_amount) are NOT in rules (calculated server-side)
- [ ] Foreign key fields validated with `Rule::exists()->where('tenant_id', TenantContext::id())`

---

## MODELS

- [ ] Model uses `HasUuids` trait (UUID primary key)
- [ ] Model uses `BelongsToTenant` trait
- [ ] Model uses `SoftDeletes` trait (added in Phase 0)
- [ ] `tenant_id` is NOT in `$fillable`
- [ ] All relationship methods defined (`belongsTo`, `hasMany`, etc.)
- [ ] `$casts` array defined for dates and decimals
- [ ] `$fillable` reviewed — no sensitive fields (status, calculated amounts) writable by user input

---

## POLICY

- [ ] Policy file exists: `app/Policies/{Model}Policy.php`
- [ ] Policy registered in `AppServiceProvider::boot()` via `Gate::policy()`
- [ ] `viewAny`, `view`, `create`, `update`, `delete` methods all check:
  1. The Spatie permission: `$user->can('group.module.action')`
  2. Tenant ownership: `$model->tenant_id === $user->tenant_id`
- [ ] Policy methods match controller action names

---

## SERVICES (if complex logic)

- [ ] Service class exists in `app/Services/{Domain}/{Name}Service.php`
- [ ] Service registered as singleton in `AppServiceProvider`
- [ ] Service has no HTTP layer code (no `request()`, no `redirect()`, no `response()`)
- [ ] All DB mutations in Service are wrapped in `DB::transaction()`
- [ ] Service throws `\DomainException` for business rule violations (not HTTP exceptions)
- [ ] Service is independently unit-testable (no controller dependency)

---

## BLADE VIEWS

- [ ] `@extends('backoffice.layout.mainlayout')` used
- [ ] `@section('content')` matches the reference template structure
- [ ] Reference template was copied FIRST and then modified (not written from scratch)
- [ ] No new CSS classes invented — only classes from the reference template used
- [ ] No inline `style=""` attributes except those matching the reference template
- [ ] All user-facing strings are in French (labels, placeholders, buttons, empty states, flash messages)
- [ ] `@error('field')` + `is-invalid` class on every form input
- [ ] `invalid-feedback` div after every input with `{{ $message }}`
- [ ] `old('field', $model->field ?? '')` used for all edit form values
- [ ] `@forelse` with `@empty` block on every table (not `@foreach`)
- [ ] Empty state message in French: "Aucun enregistrement trouvé."
- [ ] Pagination: `{{ $items->links() }}` present
- [ ] Action dropdown uses `dark-transparent` button class (matching reference)
- [ ] Delete form has `@method('DELETE')` and `@csrf` tokens
- [ ] Confirmation: `onclick="return confirm('...')"` on delete buttons
- [ ] `@can('permission.name')` used to show/hide action buttons
- [ ] Breadcrumb matches reference template position and markup

---

## SIDEBAR

- [ ] New module's nav item added to `sidebar.blade.php`
- [ ] Active state uses `request()->routeIs('bo.{module}.*')` pattern
- [ ] Parent nav item shows `open` class when a child route is active
- [ ] Icon used is from the existing icon set (Tabler `ti-*` or equivalent)

---

## SECURITY

- [ ] No `withoutGlobalScopes()` anywhere in the module
- [ ] No `tenant_id` accepted from HTTP request in any action
- [ ] Cross-tenant access prevented (route model binding uses TenantScope)
- [ ] All `Rule::exists()` calls include `.where('tenant_id', TenantContext::id())`
- [ ] Status fields not directly writable by user — only via Service transition method

---

## TESTS

- [ ] Feature test file created: `tests/Feature/{Domain}/{Model}CrudTest.php`
- [ ] Test covers: unauthenticated redirect, create, read, update, delete
- [ ] Test covers: cross-tenant data isolation (Tenant A cannot access Tenant B's data)
- [ ] Test covers: 403 for role missing the required permission
- [ ] Test covers: validation error returns 422 with correct field errors
- [ ] `php artisan test --filter={Module}` passes with no failures

---

## FINAL SIGN-OFF

- [ ] `php artisan route:list` shows all module routes
- [ ] `php artisan test` full suite passes
- [ ] Manual test: create → list → edit → delete flow works end-to-end
- [ ] Manual test: role without permission gets 403 (not 404 or 500)
- [ ] Flash messages appear correctly after each action
- [ ] Pagination works (create 20+ records and verify page 2 exists)
- [ ] Search filter works and persists in URL on pagination
- [ ] No PHP errors or warnings in `storage/logs/laravel.log`
