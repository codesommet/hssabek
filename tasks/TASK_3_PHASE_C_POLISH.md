# TASK 3 — Phase C : Polish & Fonctionnalités v2

**Priorité :** 🟢 NICE TO HAVE
**Estimation :** 3-4 semaines
**Objectif :** Ajouter les fonctionnalités différenciantes pour un SaaS compétitif : API REST, portail client, backup, et améliorations UI/UX.

---

## Table des matières

1. [API REST (Sanctum)](#1-api-rest-sanctum)
2. [Webhooks (Stripe / Paiement en ligne)](#2-webhooks-stripe--paiement-en-ligne)
3. [Portail Client (Customer Portal)](#3-portail-client-customer-portal)
4. [Système de Backup](#4-système-de-backup)
5. [Templates PDF supplémentaires](#5-templates-pdf-supplémentaires)
6. [Factories restantes](#6-factories-restantes)
7. [Tests avancés (Settings, Reports, SuperAdmin)](#7-tests-avancés-settings-reports-superadmin)
8. [Document Manager](#8-document-manager)
9. [Intégrations tierces](#9-intégrations-tierces)
10. [Sécurité avancée (2FA, Password policy)](#10-sécurité-avancée-2fa-password-policy)
11. [Améliorations diverses](#11-améliorations-diverses)

---

## 1. API REST (Sanctum)

### Problème
`routes/api/tenant.php` et `routes/api/webhooks.php` sont **vides**. Laravel Sanctum est installé mais pas utilisé.

### Architecture

```
app/Http/Controllers/Api/V1/
├── AuthController.php             ← Token creation/revocation
├── InvoiceApiController.php       ← CRUD Factures
├── CustomerApiController.php      ← CRUD Clients
├── ProductApiController.php       ← CRUD Produits
├── PaymentApiController.php       ← CRUD Paiements
├── QuoteApiController.php         ← CRUD Devis
└── ReportApiController.php        ← Endpoints rapports

app/Http/Resources/
├── InvoiceResource.php
├── InvoiceCollection.php
├── CustomerResource.php
├── CustomerCollection.php
├── ProductResource.php
├── ProductCollection.php
├── PaymentResource.php
└── QuoteResource.php
```

### Tâches détaillées

- [ ] **1.1** Configurer Sanctum pour l'authentification API multi-tenant
  - Token création avec `tenant_id` embedded
  - Middleware : `auth:sanctum` + identification tenant via token (pas via domaine)
  - Rate limiting API : 60 requests/minute par token

- [ ] **1.2** Créer `AuthController.php`
  - `POST /api/v1/auth/token` — Créer un token (email + password)
  - `DELETE /api/v1/auth/token` — Révoquer le token courant
  - `GET /api/v1/auth/user` — Info utilisateur courant

- [ ] **1.3** Créer les API Resources (JSON:API ou format simple)
  - Chaque resource transforme le modèle en JSON propre
  - Inclure les relations via `?include=items,customer`
  - Pagination via `?page=1&per_page=25`
  - Filtrage via `?status=paid&date_from=2026-01-01`

- [ ] **1.4** Créer `InvoiceApiController.php`
  ```
  GET    /api/v1/invoices          → index (liste paginée + filtres)
  POST   /api/v1/invoices          → store (créer facture)
  GET    /api/v1/invoices/{id}     → show (détail)
  PUT    /api/v1/invoices/{id}     → update (modifier)
  DELETE /api/v1/invoices/{id}     → destroy (supprimer)
  GET    /api/v1/invoices/{id}/pdf → downloadPdf
  POST   /api/v1/invoices/{id}/send → sendEmail
  ```

- [ ] **1.5** Créer `CustomerApiController.php` — même pattern CRUD
- [ ] **1.6** Créer `ProductApiController.php` — même pattern CRUD
- [ ] **1.7** Créer `PaymentApiController.php` — même pattern CRUD
- [ ] **1.8** Créer `QuoteApiController.php` — même pattern CRUD

- [ ] **1.9** Définir les routes dans `routes/api/tenant.php`
  ```php
  Route::prefix('v1')->middleware(['auth:sanctum'])->group(function () {
      Route::apiResource('invoices', InvoiceApiController::class);
      Route::get('invoices/{invoice}/pdf', [InvoiceApiController::class, 'downloadPdf']);
      Route::post('invoices/{invoice}/send', [InvoiceApiController::class, 'sendEmail']);
      Route::apiResource('customers', CustomerApiController::class);
      Route::apiResource('products', ProductApiController::class);
      Route::apiResource('payments', PaymentApiController::class);
      Route::apiResource('quotes', QuoteApiController::class);
  });
  ```

- [ ] **1.10** Créer les tests API
  - `tests/Feature/Api/InvoiceApiTest.php`
  - `tests/Feature/Api/CustomerApiTest.php`
  - Tester l'isolation tenant via tokens

- [ ] **1.11** Ajouter une page "Clés API" dans le backoffice Settings
  - Vue pour créer/révoquer des tokens Sanctum
  - La vue template `api-keys.blade.php` existe déjà dans les templates statiques

---

## 2. Webhooks (Stripe / Paiement en ligne)

### Problème
`routes/api/webhooks.php` est vide. Si le SaaS doit recevoir des paiements (abonnements), il faut un endpoint webhook.

### Fichiers à créer

```
app/Http/Controllers/Api/Webhooks/
├── StripeWebhookController.php
└── PayPalWebhookController.php (optionnel)
```

### Tâches détaillées

- [ ] **2.1** Installer `stripe/stripe-php`
  ```bash
  composer require stripe/stripe-php
  ```

- [ ] **2.2** Créer `StripeWebhookController.php`
  - Gérer les events :
    - `checkout.session.completed` → Activer l'abonnement
    - `invoice.payment_succeeded` → Renouveler l'abonnement
    - `invoice.payment_failed` → Notifier le tenant
    - `customer.subscription.deleted` → Suspendre le tenant
  - Vérifier la signature Stripe (`Stripe-Signature` header)
  - Idempotent (ne pas traiter deux fois le même event)

- [ ] **2.3** Définir la route dans `routes/api/webhooks.php`
  ```php
  Route::post('stripe', [StripeWebhookController::class, 'handle'])
      ->name('webhooks.stripe')
      ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
  ```

- [ ] **2.4** Ajouter les variables `.env` :
  ```env
  STRIPE_KEY=pk_test_...
  STRIPE_SECRET=sk_test_...
  STRIPE_WEBHOOK_SECRET=whsec_...
  ```

- [ ] **2.5** Créer un service `app/Services/Billing/StripeService.php`
  - `createCheckoutSession(Plan $plan, Tenant $tenant)` : crée une session Checkout
  - `handleWebhook(Request $request)` : dispatch les events
  - `cancelSubscription(Subscription $subscription)` : annule côté Stripe

---

## 3. Portail Client (Customer Portal)

### Problème
Le dossier `app/Http/Controllers/frontoffice/` est vide. Aucune vue dans `resources/views/frontoffice/`. Le portail client est un **différenciateur SaaS majeur**.

### Architecture

```
app/Http/Controllers/Frontoffice/
├── CustomerAuthController.php     ← Login/Register client
├── CustomerDashboardController.php
├── CustomerInvoiceController.php  ← Voir factures + télécharger PDF
├── CustomerQuoteController.php    ← Voir devis + accepter/refuser
├── CustomerPaymentController.php  ← Historique paiements
└── CustomerProfileController.php  ← Profil client

resources/views/frontoffice/
├── layout/
│   ├── app.blade.php
│   ├── header.blade.php
│   └── sidebar.blade.php
├── auth/
│   ├── login.blade.php
│   └── register.blade.php
├── dashboard.blade.php
├── invoices/
│   ├── index.blade.php
│   └── show.blade.php
├── quotes/
│   ├── index.blade.php
│   └── show.blade.php
├── payments/
│   └── index.blade.php
└── profile/
    └── edit.blade.php
```

### Tâches détaillées

- [ ] **3.1** Concevoir le système d'authentification client
  - Option A : Token magique par email (lien unique par facture/devis)
  - Option B : Compte client avec mot de passe (table `customers` + password)
  - Option C : Guard séparé `customer` dans `config/auth.php`
  - **Recommandation :** Option A pour commencer (plus simple, plus sécurisé)

- [ ] **3.2** Créer les routes dans un nouveau fichier `routes/frontoffice.php`
  ```php
  Route::prefix('portal')->name('portal.')->group(function () {
      Route::get('login', [CustomerAuthController::class, 'showLogin'])->name('login');
      Route::post('login', [CustomerAuthController::class, 'login']);

      Route::middleware('auth:customer')->group(function () {
          Route::get('/', [CustomerDashboardController::class, 'index'])->name('dashboard');
          Route::get('invoices', [CustomerInvoiceController::class, 'index'])->name('invoices.index');
          Route::get('invoices/{invoice}', [CustomerInvoiceController::class, 'show'])->name('invoices.show');
          Route::get('invoices/{invoice}/pdf', [CustomerInvoiceController::class, 'downloadPdf'])->name('invoices.pdf');
          Route::get('quotes', [CustomerQuoteController::class, 'index'])->name('quotes.index');
          Route::get('quotes/{quote}', [CustomerQuoteController::class, 'show'])->name('quotes.show');
          Route::post('quotes/{quote}/accept', [CustomerQuoteController::class, 'accept'])->name('quotes.accept');
          Route::post('quotes/{quote}/reject', [CustomerQuoteController::class, 'reject'])->name('quotes.reject');
          Route::get('payments', [CustomerPaymentController::class, 'index'])->name('payments.index');
      });
  });
  ```

- [ ] **3.3** Créer le layout frontoffice
  - Basé sur les templates statiques `customer-*.blade.php` existants
  - Design plus simple que le backoffice
  - Logo du tenant, navigation minimale

- [ ] **3.4** Créer les vues du portail (voir structure ci-dessus)
  - **Dashboard** : résumé (factures en attente, montant dû, derniers paiements)
  - **Factures** : liste des factures du client + téléchargement PDF
  - **Devis** : liste + accepter/refuser avec commentaire
  - **Paiements** : historique des paiements reçus

- [ ] **3.5** Sécuriser le portail
  - Un client ne peut voir que SES factures/devis/paiements
  - Scope par `customer_id` en plus du `tenant_id`
  - Rate limiting sur le login client

- [ ] **3.6** Ajouter les routes dans `RouteServiceProvider` ou `bootstrap/app.php`

---

## 4. Système de Backup

### Fichiers à créer

```
app/Console/Commands/DatabaseBackupCommand.php
```

### Tâches détaillées

- [ ] **4.1** Option A — Installer `spatie/laravel-backup` :
  ```bash
  composer require spatie/laravel-backup
  php artisan vendor:publish --provider="Spatie\Backup\BackupServiceProvider"
  ```
  - Configurer `config/backup.php` : DB only, stockage sur S3 ou local
  - Ajouter au scheduler : `Schedule::command('backup:run')->dailyAt('02:00')`
  - Ajouter nettoyage : `Schedule::command('backup:clean')->dailyAt('03:00')`

- [ ] **4.2** Option B — Commande manuelle simple (si pas de package) :
  ```php
  // DatabaseBackupCommand.php
  // mysqldump + compression + stockage dans storage/backups/
  ```

- [ ] **4.3** Ajouter une page "Sauvegardes" dans le SuperAdmin
  - Liste des backups existants
  - Bouton "Lancer une sauvegarde"
  - Téléchargement des backups

---

## 5. Templates PDF supplémentaires

### Problème
Certains documents n'ont qu'un seul template PDF (debit note, goods receipt, vendor bill, payment receipt). 4 modèles par type est le standard.

### Tâches détaillées

- [ ] **5.1** Créer 3 templates supplémentaires pour `Debit Note`
  ```
  resources/views/pdf/templates/free/debit-note/
  ├── template-1.blade.php  ← EXISTE
  ├── template-2.blade.php  ← À CRÉER
  ├── template-3.blade.php  ← À CRÉER
  └── template-4.blade.php  ← À CRÉER
  ```

- [ ] **5.2** Créer 3 templates supplémentaires pour `Goods Receipt`
- [ ] **5.3** Créer 3 templates supplémentaires pour `Vendor Bill`
- [ ] **5.4** Créer 3 templates supplémentaires pour `Payment Receipt`
- [ ] **5.5** Créer 3 templates supplémentaires pour `Supplier Payment Receipt`

- [ ] **5.6** Pour chaque nouveau template :
  - Copier le template-1 existant comme base
  - Modifier le design (couleurs, disposition, style)
  - S'assurer que toutes les variables dynamiques sont correctes
  - Tester la génération PDF avec `PdfService`

- [ ] **5.7** Mettre à jour `TemplateCatalogSeeder` pour inclure les nouveaux templates

---

## 6. Factories restantes

### Problème
Après Phase B (25 factories), il reste ~17 factories secondaires.

### Tâches détaillées

- [ ] **6.1** Créer les factories restantes :

| # | Factory | Modèle |
|---|---------|--------|
| 1 | `SupplierPaymentMethodFactory` | `Purchases\SupplierPaymentMethod` |
| 2 | `SupplierPaymentAllocationFactory` | `Purchases\SupplierPaymentAllocation` |
| 3 | `SignatureFactory` | `Tenancy\Signature` |
| 4 | `IntegrationFactory` | `Tenancy\Integration` |
| 5 | `DocumentFactory` | `System\Document` |
| 6 | `DocumentNumberSequenceFactory` | `System\DocumentNumberSequence` |
| 7 | `EmailLogFactory` | `System\EmailLog` |
| 8 | `ActivityLogFactory` | `System\ActivityLog` |
| 9 | `NotificationLogFactory` | `System\NotificationLog` |
| 10 | `LoginLogFactory` | `System\LoginLog` |
| 11 | `TemplateCatalogFactory` | `Templates\TemplateCatalog` |
| 12 | `TemplatePurchaseFactory` | `Templates\TemplatePurchase` |
| 13 | `TenantTemplateFactory` | `Templates\TenantTemplate` |
| 14 | `TenantTemplatePreferenceFactory` | `Templates\TenantTemplatePreference` |
| 15 | `AnnouncementFactory` | `System\Announcement` |
| 16 | `DeleteAccountRequestFactory` | `System\DeleteAccountRequest` |
| 17 | `CustomReportFactory` | `Reports\CustomReport` |

---

## 7. Tests avancés (Settings, Reports, SuperAdmin)

### Tâches détaillées

- [ ] **7.1** Créer `tests/Feature/Settings/` — un test par page settings :
  | Test | Page |
  |------|------|
  | `CompanySettingsTest` | ✅ EXISTE |
  | `InvoiceSettingsTest` | Paramètres facture |
  | `CurrencySettingsTest` | Devises |
  | `PaymentMethodSettingsTest` | Méthodes de paiement |
  | `EmailTemplateSettingsTest` | Templates email |
  | `SecuritySettingsTest` | Sécurité |
  | `AppearanceSettingsTest` | Apparence |

- [ ] **7.2** Créer `tests/Feature/Reports/` :
  | Test | Rapport |
  |------|---------|
  | `SalesReportTest` | Rapport ventes |
  | `PurchasesReportTest` | Rapport achats |
  | `ExpenseReportTest` | Rapport dépenses |
  | `ProfitLossReportTest` | Résultat |
  | `TaxReportTest` | Rapport TVA |
  | `CustomerDueReportTest` | Créances clients |

- [ ] **7.3** Créer `tests/Feature/SuperAdmin/` :
  | Test | Module |
  |------|--------|
  | `TenantManagementTest` | Gestion tenants |
  | `PlanManagementTest` | Gestion plans |
  | `SubscriptionManagementTest` | Gestion abonnements |
  | `TemplateCatalogTest` | Catalogue templates |
  | `AnnouncementTest` | Annonces |

- [ ] **7.4** Créer `tests/Feature/Roles/RolesPermissionsTest.php`
  - Test CRUD rôles
  - Test assignation permissions
  - Test que les permissions sont respectées sur chaque module

---

## 8. Document Manager

### Problème
Le modèle `Document` et la migration existent mais aucun controller ni route.

### Fichiers à créer

```
app/Http/Controllers/Backoffice/System/DocumentController.php
resources/views/backoffice/system/documents/
├── index.blade.php
└── _upload-modal.blade.php
```

### Tâches détaillées

- [ ] **8.1** Créer les routes dans un nouveau `routes/backoffice/documents.php`
  ```php
  Route::resource('documents', DocumentController::class)->names('bo.documents');
  ```

- [ ] **8.2** Créer `DocumentController.php`
  - `index()` : liste des documents uploadés (avec filtres par type, date)
  - `store()` : upload via Spatie MediaLibrary
  - `show()` : télécharger/preview
  - `destroy()` : supprimer

- [ ] **8.3** Créer les vues
  - Basé sur `file-manager.blade.php` (template statique existant)
  - Upload drag & drop
  - Tableau avec : nom, type, taille, date upload, actions

- [ ] **8.4** Lier les documents aux entités
  - Un document peut être lié à une facture, un devis, un client, etc.
  - Via polymorphic relation (ou via MediaLibrary collections)

---

## 9. Intégrations tierces

### Problème
Le modèle `Integration` existe mais aucune UI.

### Tâches détaillées

- [ ] **9.1** Créer les routes dans `routes/backoffice/settings.php`
- [ ] **9.2** Créer `IntegrationSettingsController.php`
  - Liste des intégrations disponibles (Stripe, PayPal, Mailchimp, etc.)
  - Configuration par intégration (clés API, activation)
- [ ] **9.3** Créer les vues
  - Basé sur `integrations-settings.blade.php` (template statique existant)
  - Chaque intégration a un toggle on/off + champs de configuration

---

## 10. Sécurité avancée (2FA, Password policy)

### Problème
La vue `two-step-verification.blade.php` existe mais l'implémentation 2FA n'est pas vérifiée. La politique de mot de passe n'est pas documentée.

### Tâches détaillées

- [ ] **10.1** Vérifier et compléter l'implémentation 2FA
  - Si pas implémenté : utiliser `pragmarx/google2fa-laravel` ou `laravel/fortify`
  - Ajouter les colonnes `two_factor_secret`, `two_factor_recovery_codes` à `users` si manquantes
  - Créer le controller 2FA : activation, vérification, codes de récupération

- [ ] **10.2** Renforcer la politique de mot de passe
  - Vérifier `RegisterRequest` et `ResetPasswordRequest`
  - Minimum : 8 caractères, 1 majuscule, 1 chiffre, 1 caractère spécial
  - Utiliser `Password::min(8)->mixedCase()->numbers()->symbols()`

- [ ] **10.3** Ajouter la vérification de fichiers uploadés
  - Vérifier les types MIME réels (pas juste l'extension)
  - Limiter la taille des fichiers
  - Scanner les noms de fichiers (éviter les path traversal)
  - Stocker hors du public path

---

## 11. Améliorations diverses

### Tâches détaillées

- [ ] **11.1** Cleanup des commandes Artisan :
  - `CleanupExpiredInvitationsCommand` — supprimer les invitations > 7 jours
  - `PruneActivityLogsCommand` — purger les logs > 90 jours
  - Ajouter au scheduler

- [ ] **11.2** Events supplémentaires :
  | Event | Quand |
  |-------|-------|
  | `QuoteAccepted` | Devis accepté par client (portail) |
  | `QuoteRejected` | Devis refusé par client |
  | `TenantCreated` | Nouveau tenant créé |
  | `UserRegistered` | Nouvel utilisateur inscrit |

- [ ] **11.3** Dashboard analytics amélioré
  - Graphiques de tendance (ventes sur 12 mois)
  - Top 5 clients par CA
  - Factures en retard (alertes)
  - Trésorerie (cash flow preview)

- [ ] **11.4** Multi-langue (préparation)
  - Extraire toutes les chaînes françaises dans `lang/fr/`
  - Ajouter un sélecteur de langue dans les settings
  - Préparer les fichiers `lang/en/` (traduction anglaise)

- [ ] **11.5** SEO pour les pages publiques
  - Meta tags dynamiques (title, description, og:image)
  - Sitemap XML (`route('sitemap')`)
  - Robots.txt

---

## Résumé des fichiers à créer

### Nouveaux fichiers (~80+)

| Catégorie | Nombre estimé |
|-----------|:------------:|
| API Controllers | 7 |
| API Resources | 8 |
| API Tests | 5 |
| Webhook Controller | 1 |
| Stripe Service | 1 |
| Frontoffice Controllers | 5 |
| Frontoffice Views | 12 |
| Frontoffice Layout | 3 |
| PDF Templates | 15 |
| Factories | 17 |
| Feature Tests | 20 |
| Document Manager | 3 |
| Integration Settings | 2 |
| 2FA | 2 |
| Artisan Commands | 2 |
| Events/Listeners | 6 |
| Exports (lang files) | ~10 |

### Fichiers à modifier

| Fichier | Modification |
|---------|-------------|
| `routes/api/tenant.php` | Toutes les routes API REST |
| `routes/api/webhooks.php` | Route webhook Stripe |
| `composer.json` | Packages (stripe, backup, google2fa) |
| `config/auth.php` | Guard customer (si portail) |
| `database/seeders/TemplateCatalogSeeder.php` | Nouveaux templates PDF |
| `.env.example` | Variables Stripe, 2FA |
| `routes/console.php` | Commandes cleanup/prune |
| `bootstrap/app.php` ou `RouteServiceProvider` | Charger routes frontoffice |

---

## Ordre d'implémentation recommandé

1. **API REST** (1.1-1.11) — Immédiate valeur ajoutée, permet les intégrations
2. **Templates PDF** (5.1-5.7) — Facile, améliore le produit visuellement
3. **Factories restantes** (6.1) — Supporte les tests
4. **Tests avancés** (7.1-7.4) — Solidifie la qualité
5. **Portail Client** (3.1-3.6) — Plus gros chantier, mais fort différenciateur
6. **Webhooks Stripe** (2.1-2.5) — Nécessaire si paiement en ligne
7. **Sécurité 2FA** (10.1-10.3) — Important pour la confiance
8. **Document Manager** (8.1-8.4) — Nice to have
9. **Backup** (4.1-4.3) — Nice to have
10. **Intégrations** (9.1-9.3) — v2+

---

> **Critère de succès :** L'application dispose d'une API REST fonctionnelle, d'un portail client basique, de 100% des factories, d'une couverture de tests > 60%, et d'un système de paiement en ligne via Stripe.
