# Phase 1 — Users & Company Settings

> **Depends on:** Phase 0 complete
> **Complexity:** M
> **Why first:** Tenant admins need to invite their team and configure their company
> before they can create customers or invoices.

---

## 1. Objective

Enable tenant admins to:
1. View and manage all users in their tenant (list, activate, deactivate)
2. Invite new team members via email
3. Assign roles to users
4. Configure company profile, invoice settings, and localization stored in `TenantSetting`

---

## 2. Scope

**Routes:** Extend `routes/backoffice/settings.php` + new `routes/backoffice/users.php`
**Controllers:**
- `app/Http/Controllers/Backoffice/Users/UserController.php` (new)
- `app/Http/Controllers/Backoffice/Users/UserInvitationController.php` (new)
- `app/Http/Controllers/Backoffice/Settings/CompanySettingsController.php` (new)
- `app/Http/Controllers/Backoffice/Settings/InvoiceSettingsController.php` (new)
- `app/Http/Controllers/Backoffice/Settings/LocalizationSettingsController.php` (new)

**Models used (existing — do not modify schema):**
- `App\Models\User` — `tenant_id`, `name`, `email`, `phone`, `status`, `last_login_at`, `last_login_ip`
- `App\Models\System\UserInvitation` — `tenant_id`, `email`, `role_id`, `token`, `accepted_at`, `expires_at`
- `App\Models\Tenancy\TenantSetting` — `tenant_id`, `account_settings` (JSON), `company_settings` (JSON), `localization_settings` (JSON), `invoice_settings` (JSON)
- `App\Models\Tenancy\Role` — `tenant_id`, `name`, `guard_name`

---

## 3. Required Architectural Placement

| Concern | File | Type |
|---------|------|------|
| Invite validation | `app/Http/Requests/Users/InviteUserRequest.php` | FormRequest |
| Update user validation | `app/Http/Requests/Users/UpdateUserRequest.php` | FormRequest |
| Company settings validation | `app/Http/Requests/Settings/UpdateCompanySettingsRequest.php` | FormRequest |
| Invoice settings validation | `app/Http/Requests/Settings/UpdateInvoiceSettingsRequest.php` | FormRequest |
| Localization validation | `app/Http/Requests/Settings/UpdateLocalizationSettingsRequest.php` | FormRequest |
| User permissions | `app/Policies/UserPolicy.php` | Policy |
| Settings permissions | `app/Policies/SettingsPolicy.php` | Policy |
| Invitation email | `app/Notifications/UserInvitationNotification.php` | Notification |
| Send invite async | `app/Jobs/SendUserInvitationJob.php` | Job |

**No complex Service needed** — settings are JSON column updates; invitation is a model create + queued notification.

---

## 4. Ordered Task Breakdown

### Task 1.1 — Create `routes/backoffice/users.php`

```php
<?php

use App\Http\Controllers\Backoffice\Users\UserController;
use App\Http\Controllers\Backoffice\Users\UserInvitationController;
use Illuminate\Support\Facades\Route;

Route::prefix('users')->as('users.')->group(function () {
    Route::get('/',                  [UserController::class, 'index'])->name('index');
    Route::get('/{user}/edit',       [UserController::class, 'edit'])->name('edit');
    Route::put('/{user}',            [UserController::class, 'update'])->name('update');
    Route::post('/{user}/activate',  [UserController::class, 'activate'])->name('activate');
    Route::post('/{user}/deactivate',[UserController::class, 'deactivate'])->name('deactivate');

    // Invitations
    Route::get('/invite',            [UserInvitationController::class, 'create'])->name('invite');
    Route::post('/invite',           [UserInvitationController::class, 'store'])->name('invite.store');
    Route::delete('/invite/{invitation}', [UserInvitationController::class, 'destroy'])->name('invite.destroy');
});

// Public route for accepting an invitation (no auth required)
Route::get('/accept-invitation/{token}', [UserInvitationController::class, 'accept'])
    ->name('invitation.accept')
    ->withoutMiddleware(['auth']);
Route::post('/accept-invitation/{token}', [UserInvitationController::class, 'acceptStore'])
    ->name('invitation.accept.store')
    ->withoutMiddleware(['auth']);
```

Include in `routes/web.php` inside the backoffice `auth` group.

### Task 1.2 — Extend `routes/backoffice/settings.php`

Add company, invoice, and localization settings routes:
```php
use App\Http\Controllers\Backoffice\Settings\CompanySettingsController;
use App\Http\Controllers\Backoffice\Settings\InvoiceSettingsController;
use App\Http\Controllers\Backoffice\Settings\LocalizationSettingsController;

Route::prefix('settings')->as('settings.')->group(function () {
    // Already existing: account settings routes

    // Company settings
    Route::get('/company',  [CompanySettingsController::class, 'edit'])->name('company');
    Route::put('/company',  [CompanySettingsController::class, 'update'])->name('company.update');

    // Invoice settings
    Route::get('/invoice',  [InvoiceSettingsController::class, 'edit'])->name('invoice');
    Route::put('/invoice',  [InvoiceSettingsController::class, 'update'])->name('invoice.update');

    // Localization settings
    Route::get('/locale',   [LocalizationSettingsController::class, 'edit'])->name('locale');
    Route::put('/locale',   [LocalizationSettingsController::class, 'update'])->name('locale.update');
});
```

### Task 1.3 — Implement `UserController`

```php
// app/Http/Controllers/Backoffice/Users/UserController.php
public function index(Request $request)
{
    abort_unless(auth()->user()->can('access.users.view'), 403);

    $users = User::query()
        ->where('tenant_id', TenantContext::id())  // explicit scope (defense-in-depth)
        ->with('roles')
        ->when($request->search, fn($q, $s) => $q->where('name', 'like', "%{$s}%")
            ->orWhere('email', 'like', "%{$s}%"))
        ->latest()
        ->paginate(15)
        ->withQueryString();

    $pendingInvitations = UserInvitation::where('tenant_id', TenantContext::id())
        ->whereNull('accepted_at')
        ->get();

    return view('backoffice.users.index', compact('users', 'pendingInvitations'));
}

public function activate(User $user)
{
    abort_unless(auth()->user()->can('access.users.edit'), 403);
    $this->assertSameTenant($user);
    $user->update(['status' => 'active']);
    return redirect()->route('bo.users.index')->with('success', 'Utilisateur activé.');
}

public function deactivate(User $user)
{
    abort_unless(auth()->user()->can('access.users.edit'), 403);
    $this->assertSameTenant($user);
    abort_if($user->id === auth()->id(), 403, 'Vous ne pouvez pas vous désactiver vous-même.');
    $user->update(['status' => 'inactive']);
    return redirect()->route('bo.users.index')->with('success', 'Utilisateur désactivé.');
}

private function assertSameTenant(User $user): void
{
    abort_unless($user->tenant_id === TenantContext::id(), 403);
}
```

### Task 1.4 — Implement `UserInvitationController`

```php
// app/Http/Controllers/Backoffice/Users/UserInvitationController.php
public function store(InviteUserRequest $request)
{
    abort_unless(auth()->user()->can('access.users.create'), 403);

    // Check if email already exists in this tenant
    $existingUser = User::where('email', $request->email)
        ->where('tenant_id', TenantContext::id())
        ->exists();

    abort_if($existingUser, 422, 'Cet utilisateur existe déjà dans votre organisation.');

    // Check for existing pending invitation
    UserInvitation::where('tenant_id', TenantContext::id())
        ->where('email', $request->email)
        ->whereNull('accepted_at')
        ->delete(); // Cancel any existing invite

    $invitation = UserInvitation::create([
        // tenant_id filled by BelongsToTenant
        'email'      => $request->email,
        'role_id'    => $request->role_id,
        'token'      => \Str::random(64),
        'expires_at' => now()->addDays(7),
    ]);

    dispatch(new SendUserInvitationJob($invitation));

    return redirect()->route('bo.users.index')
        ->with('success', 'Invitation envoyée à ' . $request->email . '.');
}

public function accept(string $token)
{
    $invitation = UserInvitation::where('token', $token)
        ->whereNull('accepted_at')
        ->where('expires_at', '>', now())
        ->firstOrFail();

    return view('backoffice.users.accept-invitation', compact('invitation'));
}

public function acceptStore(string $token, Request $request)
{
    $invitation = UserInvitation::where('token', $token)
        ->whereNull('accepted_at')
        ->where('expires_at', '>', now())
        ->firstOrFail();

    $request->validate([
        'name'     => ['required', 'string', 'max:255'],
        'password' => ['required', 'min:8', 'confirmed'],
    ]);

    $user = User::create([
        'tenant_id'          => $invitation->tenant_id,
        'email'              => $invitation->email,
        'name'               => $request->name,
        'password'           => bcrypt($request->password),
        'status'             => 'active',
        'email_verified_at'  => now(),
    ]);

    $role = Role::find($invitation->role_id);
    if ($role) {
        $user->syncRoles([$role]);
    }

    $invitation->update(['accepted_at' => now()]);

    auth()->login($user);

    return redirect()->route('bo.dashboard')
        ->with('success', 'Bienvenue ! Votre compte a été créé avec succès.');
}
```

### Task 1.5 — Implement Company Settings Controllers

```php
// app/Http/Controllers/Backoffice/Settings/CompanySettingsController.php
public function edit()
{
    abort_unless(auth()->user()->can('settings.company.view'), 403);
    $settings = TenantContext::get()->settings;
    return view('backoffice.settings.company', compact('settings'));
}

public function update(UpdateCompanySettingsRequest $request)
{
    abort_unless(auth()->user()->can('settings.company.edit'), 403);
    $tenant  = TenantContext::get();
    $setting = $tenant->settings ?? new TenantSetting(['tenant_id' => $tenant->id]);

    $setting->company_settings = array_merge(
        $setting->company_settings ?? [],
        $request->validated()
    );
    $setting->save();

    return redirect()->route('bo.settings.company')
        ->with('success', 'Paramètres de l\'entreprise mis à jour.');
}
```

Use same pattern for `InvoiceSettingsController` and `LocalizationSettingsController`,
targeting `invoice_settings` and `localization_settings` JSON columns respectively.

After localization update, call `app()->setLocale()` and `config(['app.timezone' => ...])`.
The `SetTenantContext` middleware will pick up the new values on the next request automatically.

### Task 1.6 — Create `UserInvitationNotification` and `SendUserInvitationJob`

```php
// app/Notifications/UserInvitationNotification.php
public function toMail(mixed $notifiable): MailMessage
{
    $url = route('bo.invitation.accept', $this->invitation->token);
    return (new MailMessage)
        ->subject('Invitation à rejoindre ' . $this->invitation->tenant->name)
        ->greeting('Bonjour,')
        ->line('Vous avez été invité(e) à rejoindre ' . $this->invitation->tenant->name . '.')
        ->action('Accepter l\'invitation', $url)
        ->line('Ce lien expire dans 7 jours.');
}
```

```php
// app/Jobs/SendUserInvitationJob.php
class SendUserInvitationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly UserInvitation $invitation) {}

    public function handle(): void
    {
        // Set tenant context for any model queries inside
        TenantContext::set($this->invitation->tenant);
        Notification::route('mail', $this->invitation->email)
            ->notify(new UserInvitationNotification($this->invitation));
    }
}
```

### Task 1.7 — Create FormRequests

`app/Http/Requests/Users/InviteUserRequest.php`:
```php
public function rules(): array
{
    return [
        'email'   => ['required', 'email', 'max:255'],
        'role_id' => ['required', 'uuid', Rule::exists('roles', 'id')
            ->where('tenant_id', TenantContext::id())],
    ];
}
public function messages(): array
{
    return [
        'email.required'   => 'L\'adresse email est obligatoire.',
        'email.email'      => 'L\'adresse email n\'est pas valide.',
        'role_id.required' => 'Le rôle est obligatoire.',
        'role_id.exists'   => 'Le rôle sélectionné n\'existe pas.',
    ];
}
```

### Task 1.8 — Register Policies in AppServiceProvider

```php
// app/Providers/AppServiceProvider.php
use Illuminate\Support\Facades\Gate;

public function boot(): void
{
    Gate::policy(\App\Models\User::class, \App\Policies\UserPolicy::class);
}
```

### Task 1.9 — Create Blade Views

**Reference files to mirror:**
- `index.blade.php` → copy from `resources/views/users.blade.php`
- `invite.blade.php` → copy from `resources/views/add-*.blade.php` pattern
- `backoffice/settings/company.blade.php` → copy from `resources/views/company-settings.blade.php`
- `backoffice/settings/invoice.blade.php` → copy from `resources/views/invoice-settings.blade.php`
- `backoffice/settings/locale.blade.php` → copy from `resources/views/authentication-settings.blade.php` (closest pattern)

**Dynamic replacements (per CLAUDE.md):**
```blade
@forelse($users as $user)
    <tr>
        <td>{{ $user->name }}</td>
        <td>{{ $user->email }}</td>
        <td>
            <span class="badge {{ $user->status === 'active' ? 'bg-success' : 'bg-danger' }}">
                {{ $user->status === 'active' ? 'Actif' : 'Inactif' }}
            </span>
        </td>
        <td>{{ $user->roles->pluck('name')->implode(', ') ?: '—' }}</td>
        <td>
            <div class="dropdown">
                <button class="btn btn-sm dark-transparent dropdown-toggle" data-bs-toggle="dropdown">
                    Actions
                </button>
                <ul class="dropdown-menu">
                    <li>
                        <a class="dropdown-item" href="{{ route('bo.users.edit', $user) }}">
                            <i class="ti ti-edit me-1"></i> Modifier
                        </a>
                    </li>
                    @if($user->status === 'active')
                    <li>
                        <form method="POST" action="{{ route('bo.users.deactivate', $user) }}">
                            @csrf
                            <button class="dropdown-item text-warning" type="submit">
                                <i class="ti ti-lock me-1"></i> Désactiver
                            </button>
                        </form>
                    </li>
                    @else
                    <li>
                        <form method="POST" action="{{ route('bo.users.activate', $user) }}">
                            @csrf
                            <button class="dropdown-item text-success" type="submit">
                                <i class="ti ti-lock-open me-1"></i> Activer
                            </button>
                        </form>
                    </li>
                    @endif
                </ul>
            </div>
        </td>
    </tr>
@empty
    <tr><td colspan="5" class="text-center">Aucun utilisateur trouvé.</td></tr>
@endforelse
```

### Task 1.10 — Update Sidebar

Add Users and Settings sections to `sidebar.blade.php`:
```blade
{{-- USERS --}}
<li class="nav-item">
    <a href="{{ route('bo.users.index') }}"
       class="nav-link {{ request()->routeIs('bo.users.*') ? 'active' : '' }}">
        <i class="ti ti-users"></i>
        <span>Utilisateurs</span>
    </a>
</li>

{{-- SETTINGS --}}
<li class="nav-item has-sub {{ request()->routeIs('bo.settings.*') ? 'open' : '' }}">
    <a href="#" class="nav-link">
        <i class="ti ti-settings"></i>
        <span>Paramètres</span>
    </a>
    <ul class="submenu">
        <li><a href="{{ route('bo.settings.company') }}" class="nav-link">Entreprise</a></li>
        <li><a href="{{ route('bo.settings.invoice') }}" class="nav-link">Facturation</a></li>
        <li><a href="{{ route('bo.settings.locale') }}"  class="nav-link">Localisation</a></li>
    </ul>
</li>
```

---

## 5. Deliverables

| File | Action |
|------|--------|
| `routes/backoffice/users.php` | New |
| `routes/backoffice/settings.php` | Extended |
| `app/Http/Controllers/Backoffice/Users/UserController.php` | New |
| `app/Http/Controllers/Backoffice/Users/UserInvitationController.php` | New |
| `app/Http/Controllers/Backoffice/Settings/CompanySettingsController.php` | New |
| `app/Http/Controllers/Backoffice/Settings/InvoiceSettingsController.php` | New |
| `app/Http/Controllers/Backoffice/Settings/LocalizationSettingsController.php` | New |
| `app/Http/Requests/Users/InviteUserRequest.php` | New |
| `app/Http/Requests/Users/UpdateUserRequest.php` | New |
| `app/Http/Requests/Settings/UpdateCompanySettingsRequest.php` | New |
| `app/Http/Requests/Settings/UpdateInvoiceSettingsRequest.php` | New |
| `app/Http/Requests/Settings/UpdateLocalizationSettingsRequest.php` | New |
| `app/Policies/UserPolicy.php` | New |
| `app/Notifications/UserInvitationNotification.php` | New |
| `app/Jobs/SendUserInvitationJob.php` | New |
| `resources/views/backoffice/users/index.blade.php` | New |
| `resources/views/backoffice/users/edit.blade.php` | New |
| `resources/views/backoffice/users/invite.blade.php` | New |
| `resources/views/backoffice/users/accept-invitation.blade.php` | New |
| `resources/views/backoffice/settings/company.blade.php` | New |
| `resources/views/backoffice/settings/invoice.blade.php` | New |
| `resources/views/backoffice/settings/locale.blade.php` | New |
| `app/Providers/AppServiceProvider.php` | Policy registration |

---

## 6. Acceptance Criteria

- [ ] Tenant admin sees all users in their tenant only (not other tenants' users)
- [ ] Invite form sends email via queue (check `jobs` table)
- [ ] Invitation link works without auth → creates user account → auto-login
- [ ] Expired invitation (>7 days) → 404
- [ ] User cannot be deactivated by themselves
- [ ] Company settings saved to `tenant_settings.company_settings` JSON column
- [ ] Invoice prefix saved → used by `DocumentNumberService` on next invoice creation
- [ ] Timezone change → applied to next page load (via `SetTenantContext` middleware)
- [ ] User with `access.users.view` permission can see list; without it → 403
- [ ] Pending invitations shown separately in users list

---

## 7. Tests Required

| Test File | Type | Covers |
|-----------|------|--------|
| `tests/Feature/Users/UserManagementTest.php` | Feature | List, activate, deactivate, cross-tenant isolation |
| `tests/Feature/Users/UserInvitationTest.php` | Feature | Invite flow, accept, expired token |
| `tests/Feature/Settings/CompanySettingsTest.php` | Feature | Save settings, locale change |

---

## 8. Multi-Tenant Pitfalls

- ❌ NEVER: `User::all()` — always scope to `where('tenant_id', TenantContext::id())`
- ❌ NEVER: Show invitation links from other tenants
- ✅ DO: Validate `role_id` exists within the current tenant's roles
- ✅ DO: In `acceptStore()`, use `$invitation->tenant_id` (from DB), never from request
- ✅ DO: Set TenantContext in `SendUserInvitationJob::handle()` before any model access

---

## 9. Mass-Assignment Safeguards

- `UserInvitation` must NOT have `tenant_id` in `$fillable` — `BelongsToTenant` fills it
- `User` must NOT have `tenant_id` in `$fillable` — explicitly set from `$invitation->tenant_id`
- In `acceptStore()`, never trust `$request->tenant_id` — always use `$invitation->tenant_id`
- Settings controllers: only update specific JSON keys, never allow overwriting the entire settings record from request data

---

## 10. Schema Notes (Existing — Do Not Modify)

**`user_invitations` table has:** `tenant_id`, `email`, `role_id`, `token`, `accepted_at`, `expires_at`
**`tenant_settings` table has:** `tenant_id`, `account_settings` (JSON), `company_settings` (JSON), `localization_settings` (JSON), `invoice_settings` (JSON)
**`users` table has:** `tenant_id`, `name`, `email`, `phone`, `address`, `avatar_url`, `status`, `last_login_at`, `last_login_ip`, `email_verified_at`

Do NOT rename any columns. Do NOT add columns without a new migration file.

---

## 11. UI Instructions

- **Reference for user list:** `resources/views/users.blade.php`
- **Reference for invite form:** `resources/views/add-customer.blade.php` (form pattern)
- **Reference for company settings:** `resources/views/company-settings.blade.php`
- **Reference for invoice settings:** `resources/views/invoice-settings.blade.php`
- Copy exact HTML structure — only replace static content with Blade variables
- All labels, placeholders, buttons, flash messages MUST be in French
- Use `@error('field') is-invalid @enderror` on every form input
- Use `{{ $settings->company_settings['company_name'] ?? '' }}` for JSON field access
