# AUDIT REPORT — Facturation SaaS

**Date:** 2026-03-10
**Auditor:** Claude Opus 4.6 (Automated Security & Code Audit)
**Mode:** Read-Only

---

## 1. Project Overview

| Attribute | Value |
|-----------|-------|
| **Project** | Facturation — Multi-Tenant SaaS Invoicing & Accounting Platform |
| **Stack** | Laravel 12, PHP 8.2+, Blade, Bootstrap, DomPDF |
| **Database** | MySQL (SQLite for tests) |
| **Multi-Tenancy** | Domain-based tenant identification |
| **Status** | ~80% complete |
| **Packages** | 8 production, 10 dev dependencies |

---

## 2. File Structure Summary

```
facturation/
├── app/
│   ├── Http/
│   │   ├── Controllers/          (~71 controllers)
│   │   │   ├── Auth/             (6 controllers)
│   │   │   ├── Backoffice/       (68+ controllers across 11 domains)
│   │   │   └── SuperAdmin/       (8 controllers)
│   │   ├── Middleware/           (8 middleware classes)
│   │   └── Requests/            (form validation classes)
│   ├── Models/                  (30+ Eloquent models)
│   ├── Policies/                (40+ policy files)
│   ├── Services/                (domain services: Sales, Purchases, Finance, etc.)
│   ├── Jobs/                    (SendInvoiceEmailJob, SendQuoteEmailJob)
│   └── Events/                  (4 event classes)
├── database/
│   ├── factories/               (28 model factories)
│   ├── migrations/              (~70 migration files)
│   └── seeders/                 (permission, role, template seeders)
├── resources/views/
│   ├── backoffice/              (dynamic CRUD views)
│   ├── pdf/                     (PDF templates)
│   └── *.blade.php              (500+ static reference templates)
├── routes/
│   ├── backoffice/              (14 route files by domain)
│   └── superadmin/              (admin route files)
├── tests/
│   ├── Feature/                 (tenant isolation, CRM, mass assignment tests)
│   └── Unit/                    (service tests)
└── config/                      (standard Laravel config)
```

---

## 3. Bugs Found

### BUG-1: Deleted PDF Templates May Cause Runtime Errors (Medium)

**Location:** Git status shows 19 PDF template files deleted (`resources/views/pdf/templates/classic/`, `elegant/`, `modern/`), while `PdfService.php` still references template paths via `TEMPLATED_VIEWS` constant.

**Risk:** If these files are actually removed from disk, any attempt to generate a PDF with an old template selection will throw a `ViewNotFoundException`.

**Status:** New templates exist under `resources/views/pdf/templates/free/` (untracked). The migration from old to new templates appears incomplete.

### BUG-2: Unbounded Invoice Query in Payment/CreditNote Forms (Medium)

**Location:**
- `app/Http/Controllers/Backoffice/Sales/PaymentController.php:57-62`
- `app/Http/Controllers/Backoffice/Sales/CreditNoteController.php:57-61, 104-108`

**Issue:** `Invoice::with('customer')->whereIn('status', [...])->get()` loads ALL qualifying invoices with no `->limit()`. For tenants with thousands of invoices, this causes memory exhaustion and slow page loads.

### BUG-3: Placeholder Controllers Still Present (Low)

**Location:**
- `app/Http/Controllers/Backoffice/Billing/PlanController.php`
- `app/Http/Controllers/Backoffice/Billing/SubscriptionController.php`
- `app/Http/Controllers/Backoffice/Billing/SubscriptionInvoiceController.php`

**Issue:** These contain `TODO: Remove this file if confirmed unused` comments. `SubscriptionInvoiceController` throws `abort(403)` in its constructor.

---

## 4. Security Issues

### Overall Security Rating: A (Excellent)

The project demonstrates strong security fundamentals throughout.

### SEC-1: Composer Vulnerability — `league/commonmark` (Medium)

**CVE:** CVE-2026-30838 — DisallowedRawHtml extension bypass via whitespace in HTML tag names.
**Affected version:** <=2.8.0
**Fix:** Update `league/commonmark` to latest patch.

### SEC-2: APP_DEBUG=true in .env (Medium — Production Risk)

**Issue:** `.env` has `APP_DEBUG=true`. If deployed to production without changing, stack traces with code paths, DB queries, and environment variables will be exposed.
**Recommendation:** Deployment checklist must enforce `APP_DEBUG=false`.

### SEC-3: SESSION_SECURE_COOKIE Not Enforced (Medium — Production Risk)

**Issue:** `.env.example` does not set `SESSION_SECURE_COOKIE=true`. In production over HTTPS, session cookies could be sent over insecure channels.

### SEC-4: Session Encryption Disabled (Low)

**Issue:** `SESSION_ENCRYPT=false` — session data in database is not encrypted. For financial SaaS, consider enabling.

### Verified Secure (No Issues)

| Category | Status |
|----------|--------|
| **SQL Injection** | No raw queries with user input. All queries use parameterized bindings. |
| **XSS** | All `{{ }}` output escaped. `{!! !!}` only used with `json_encode()` and `nl2br(e())`. |
| **CSRF** | 213 `@csrf` tokens across 137 blade files. All forms protected. |
| **Mass Assignment** | All models use explicit `$fillable`. No `$guarded = []` found. `tenant_id` removed from fillable on domain models. |
| **File Uploads** | Proper MIME type, size, and image validation on all upload endpoints. |
| **Authentication** | Rate limiting on login/registration. Strong password policy (8+ chars, mixed case, numbers). bcrypt with 12 rounds. |
| **Authorization** | 40+ policy files. Tenant ownership verified in every policy. Spatie permissions + custom policies. |
| **Tenant Isolation** | `BelongsToTenant` trait with global scope. `TenantContext` prevents cross-tenant queries. Domain-based identification with 404 on unknown domains. |
| **Dangerous Functions** | Zero `eval()`, `exec()`, `shell_exec()`, `system()` calls. |
| **Hardcoded Secrets** | None found. `.env` in `.gitignore`. Credentials use env variables. |
| **CSP Headers** | `ContentSecurityPolicy` middleware sets proper headers on HTML responses. |
| **Debugging Code** | No `dd()`, `dump()`, `var_dump()`, or `print_r()` in application code. |

---

## 5. Performance Issues

### PERF-1: Unbounded `.get()` Queries for Form Dropdowns (High)

**Affected Controllers:**
- `InvoiceController::create/edit` — loads ALL customers, products, units, tax groups, bank accounts, signatures
- `QuoteController::create/edit` — same pattern
- `CreditNoteController::create/edit` — loads ALL invoices without limit
- `PaymentController::create` — loads ALL invoices without limit
- `ExpenseController::create/edit` — loads all categories, bank accounts
- `LoanController`, `IncomeController`, `MoneyTransferController` — similar patterns

**Impact:** For tenants with thousands of records, page loads will be slow and memory-intensive.
**Recommendation:** Add `->limit()`, `->select(['id', 'name'])`, or use AJAX search for large collections.

### PERF-2: Missing Composite Database Indexes (Medium)

| Table | Missing Index | Used For |
|-------|---------------|----------|
| `customers` | `[tenant_id, name]` | Name search/sort |
| `expenses` | `bank_account_id`, `supplier_id`, `category_id` | Filtering |
| `vendor_bills` | `supplier_id` index (has FK but no index) | Joins |
| `payments` | `[tenant_id, payment_date]` | Date-range queries |

### PERF-3: Synchronous PDF Generation in Web Requests (Medium)

**Location:** `PdfService::invoiceResponse()`, `quoteResponse()`, etc.
**Issue:** DomPDF renders synchronously (1-3 seconds per PDF). Download endpoints block the request.
**Note:** Email PDF attachments are correctly asynchronous via queue jobs.
**Recommendation:** Cache rendered PDFs or serve pre-generated files.

### PERF-4: No Column Selection on Dropdown Queries (Low)

**Issue:** `Customer::orderBy('name')->get()` loads all columns when only `id` and `name` are needed.
**Recommendation:** Use `->select(['id', 'name'])` for dropdown data.

### PERF-5: Database as Cache/Queue/Session Driver (Low — Production Risk)

**Current:** Cache, queue, and session all use `database` driver.
**Recommendation:** Production should use Redis for all three (comments in `.env.example` already suggest this).

---

## 6. Code Smells

### SMELL-1: Missing Return Type Declarations (Medium)

50+ public methods across controllers and services lack explicit return type hints (`View`, `RedirectResponse`, `JsonResponse`, `string`). Reduces IDE support and type safety.

**Affected Areas:**
- All Backoffice controllers (CRUD methods)
- `PdfService` (response methods)
- `DocumentNumberService` (preview/next methods)

### SMELL-2: Repeated Dropdown Loading Logic (Low)

Multiple controllers load identical dropdown data (customers, products, units, tax groups, bank accounts). This logic is duplicated between `create()` and `edit()` methods within the same controller.

**Recommendation:** Extract to a private method or trait.

### SMELL-3: No npm Lock File (Low)

`npm audit` fails because no `package-lock.json` exists. Frontend dependency versions are not pinned, risking non-reproducible builds.

---

## 7. Missing Features / Incomplete Modules

### 7.1 Incomplete Template Migration
- Old PDF templates (classic, elegant, modern) deleted from git
- New templates (free/) added but untracked
- Migration path between old and new template systems unclear

### 7.2 Billing Module Placeholder
- Three billing controllers (`PlanController`, `SubscriptionController`, `SubscriptionInvoiceController`) are stubs marked TODO for removal

### 7.3 Pro Features Not Implemented
- `app/Http/Controllers/Backoffice/Pro/` directory exists with 3 controllers
- Pro tier features deferred to later phases

### 7.4 Missing Production Configuration
- No `.env.production` template
- No deployment scripts or CI/CD configuration
- No database backup/replication strategy documented

### 7.5 Missing Error Tracking
- No Sentry, Bugsnag, or similar error tracking integration
- Log rotation not configured (single `laravel.log` file grows unbounded)

### 7.6 Missing npm Lock File
- `package-lock.json` absent — frontend builds not reproducible

---

## 8. Recommendations

### Architecture & Code Quality
1. Add return type declarations to all public controller/service methods
2. Extract repeated dropdown-loading logic into shared traits or helper methods
3. Remove or properly route the 3 placeholder billing controllers
4. Generate `package-lock.json` and commit it

### Security
5. Update `league/commonmark` to patch CVE-2026-30838
6. Create a production deployment checklist enforcing `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`
7. Consider enabling `SESSION_ENCRYPT=true` for financial data protection
8. Add HTTPS enforcement middleware for production

### Performance
9. Add `->limit()` and `->select()` to all dropdown queries
10. Add missing composite indexes on frequently queried columns
11. Implement PDF caching for repeated downloads
12. Switch to Redis for cache/queue/session in production

### Deployment
13. Create `.env.production` template with secure defaults
14. Configure daily log rotation (`LOG_CHANNEL=daily`)
15. Add error tracking integration (Sentry recommended)
16. Set up CI/CD pipeline with automated tests

---

## 9. Priority Fix List

### High Priority
| # | Issue | Category | Location |
|---|-------|----------|----------|
| 1 | Unbounded `.get()` queries in form dropdowns | Performance | 6+ controllers |
| 2 | `league/commonmark` CVE-2026-30838 | Security | composer.json |
| 3 | PDF template migration incomplete (deleted vs new) | Bug | resources/views/pdf/ |

### Medium Priority
| # | Issue | Category | Location |
|---|-------|----------|----------|
| 4 | Missing composite database indexes | Performance | migrations |
| 5 | APP_DEBUG/SESSION_SECURE_COOKIE for production | Security | .env |
| 6 | Synchronous PDF generation in downloads | Performance | PdfService.php |
| 7 | Missing return type declarations (50+ methods) | Code Quality | controllers/services |
| 8 | No npm lock file (non-reproducible builds) | DevOps | package.json |

### Low Priority
| # | Issue | Category | Location |
|---|-------|----------|----------|
| 9 | Placeholder billing controllers to clean up | Code Smell | Billing/ controllers |
| 10 | Database as cache/queue/session driver | Performance | .env |
| 11 | No log rotation configured | Operations | .env / config/logging.php |
| 12 | No error tracking integration | Operations | — |
| 13 | Session encryption disabled | Security | .env |
| 14 | Duplicate dropdown-loading logic in controllers | Code Smell | controllers |

---

## 10. Conclusion

The Facturation SaaS project is well-architected with strong security fundamentals. Tenant isolation, input validation, CSRF/XSS protection, and authorization are properly implemented throughout. The main areas requiring attention are:

1. **Performance**: Unbounded queries in form dropdowns will cause issues at scale
2. **One CVE**: `league/commonmark` needs updating
3. **Production readiness**: Missing production configuration templates and log rotation
4. **Code quality**: Return type declarations and minor code duplication

No critical security vulnerabilities were found. The project is in good shape for a pre-production application at ~80% completion.

---

*Generated by automated audit — 2026-03-10*
