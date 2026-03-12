# Audit Complet — Facturation SaaS — Starter Version

**Date :** 2026-03-12
**Objectif :** Identifier tout ce qui manque ou est incomplet pour une version "Starter" prête pour la production.

---

## Table des matières

1. [Résumé exécutif](#1-résumé-exécutif)
2. [Inventaire complet du projet](#2-inventaire-complet-du-projet)
3. [CRUD : Ce qui existe vs ce qui manque](#3-crud--ce-qui-existe-vs-ce-qui-manque)
4. [Routes manquantes ou incomplètes](#4-routes-manquantes-ou-incomplètes)
5. [Vues (Blade) manquantes](#5-vues-blade-manquantes)
6. [Services métier — Analyse](#6-services-métier--analyse)
7. [Factories manquantes (Tests)](#7-factories-manquantes-tests)
8. [Emails / Notifications / Mail classes](#8-emails--notifications--mail-classes)
9. [Jobs / Queues / Scheduled Tasks](#9-jobs--queues--scheduled-tasks)
10. [API (REST)](#10-api-rest)
11. [Frontend public / Landing pages](#11-frontend-public--landing-pages)
12. [Portail client (Customer Portal)](#12-portail-client-customer-portal)
13. [Tests — Couverture actuelle](#13-tests--couverture-actuelle)
14. [Sécurité & Middleware](#14-sécurité--middleware)
15. [PDF Templates — État](#15-pdf-templates--état)
16. [Artisan Commands (Console)](#16-artisan-commands-console)
17. [Configuration / .env](#17-configuration--env)
18. [Checklist production](#18-checklist-production)
19. [Priorités recommandées](#19-priorités-recommandées)

---

## 1. Résumé exécutif

Le projet est **substantiel et bien structuré**. Il contient :
- **88 migrations** → ~45 tables métier
- **60+ modèles** organisés par domaine (Sales, Purchases, Finance, Inventory, CRM, etc.)
- **50+ contrôleurs** (Backoffice + SuperAdmin)
- **80+ Form Requests** avec validation
- **33 Policies** pour l'autorisation
- **8 Middleware** personnalisés (tenant, subscription, permission, etc.)
- **20+ Services** métier
- **90+ vues Blade** dynamiques (backoffice)
- **40+ templates PDF** (free + paid)

### Verdict global
Le backoffice tenant et le panel SuperAdmin sont **fonctionnellement complets à ~85%**. Les manques principaux sont :

| Catégorie | Statut |
|-----------|--------|
| CRUD Backoffice | ✅ 90% — quelques sous-modules manquent |
| SuperAdmin Panel | ✅ 85% — CRUD complet |
| Settings | ✅ 95% — très complet |
| PDF Generation | ✅ 90% — bon coverage |
| Email System | ⚠️ 30% — Mail classes manquantes |
| API REST | ❌ 0% — fichiers vides |
| Customer Portal | ❌ 0% — pas commencé |
| Landing Pages | ❌ 0% — vues non créées |
| Console Commands | ❌ 0% — aucun Artisan command |
| Scheduled Tasks | ❌ 0% — aucun cron configuré |
| Tests | ⚠️ 25% — base solide mais couverture faible |
| Factories | ⚠️ 40% — 42 manquantes sur ~70 |

---

## 2. Inventaire complet du projet

### 2.1 Migrations (88 fichiers)

| # | Table | Domaine | Soft Deletes |
|---|-------|---------|:---:|
| 1 | `tenants` | Tenancy | - |
| 2 | `tenant_domains` | Tenancy | - |
| 3 | `users` | Auth | - |
| 4 | `tenant_settings` | Tenancy | - |
| 5 | `signatures` | Tenancy | - |
| 6 | `integrations` | Tenancy | - |
| 7 | `product_categories` | Catalog | ✅ |
| 8 | `units` | Catalog | ✅ |
| 9 | `tax_categories` | Catalog | - |
| 10 | `tax_groups` | Catalog | - |
| 11 | `tax_group_rates` | Catalog | - |
| 12 | `currencies` | Finance | - |
| 13 | `exchange_rates` | Finance | - |
| 14 | `products` | Catalog | ✅ |
| 15 | `customers` | CRM | ✅ |
| 16 | `customer_addresses` | CRM | - |
| 17 | `customer_contacts` | CRM | - |
| 18 | `warehouses` | Inventory | ✅ |
| 19 | `product_stocks` | Inventory | - |
| 20 | `stock_movements` | Inventory | - |
| 21 | `stock_transfers` | Inventory | ✅ |
| 22 | `stock_transfer_items` | Inventory | - |
| 23 | `quotes` | Sales | ✅ |
| 24 | `quote_items` | Sales | - |
| 25 | `quote_charges` | Sales | - |
| 26 | `invoices` | Sales | ✅ |
| 27 | `invoice_items` | Sales | - |
| 28 | `invoice_charges` | Sales | - |
| 29 | `credit_notes` | Sales | ✅ |
| 30 | `credit_note_items` | Sales | - |
| 31 | `credit_note_applications` | Sales | - |
| 32 | `delivery_challans` | Sales | ✅ |
| 33 | `delivery_challan_items` | Sales | - |
| 34 | `delivery_challan_charges` | Sales | - |
| 35 | `payment_methods` | Sales | - |
| 36 | `payments` | Sales | ✅ |
| 37 | `payment_allocations` | Sales | - |
| 38 | `refunds` | Sales | - |
| 39 | `suppliers` | Purchases | ✅ |
| 40 | `purchase_orders` | Purchases | ✅ |
| 41 | `purchase_order_items` | Purchases | - |
| 42 | `goods_receipts` | Purchases | - |
| 43 | `goods_receipt_items` | Purchases | - |
| 44 | `vendor_bills` | Purchases | ✅ |
| 45 | `debit_notes` | Purchases | ✅ |
| 46 | `debit_note_items` | Purchases | - |
| 47 | `debit_note_applications` | Purchases | - |
| 48 | `supplier_payment_methods` | Purchases | - |
| 49 | `supplier_payments` | Purchases | - |
| 50 | `supplier_payment_allocations` | Purchases | - |
| 51 | `bank_accounts` | Finance | ✅ |
| 52 | `money_transfers` | Finance | - |
| 53 | `finance_categories` | Finance | - |
| 54 | `expenses` | Finance | ✅ |
| 55 | `incomes` | Finance | ✅ |
| 56 | `loans` | Finance | - |
| 57 | `loan_installments` | Finance | - |
| 58 | `documents` | System | - |
| 59 | `email_logs` | System | - |
| 60 | `activity_logs` | System | - |
| 61 | `notification_logs` | System | - |
| 62 | `login_logs` | System | - |
| 63 | `plans` | Billing | - |
| 64 | `subscriptions` | Billing | - |
| 65 | `subscription_invoices` | Billing | - |
| 66 | `template_catalog` | Templates | - |
| 67 | `tenant_templates` | Templates | - |
| 68 | `tenant_template_preferences` | Templates | - |
| 69 | `template_purchases` | Templates | - |
| 70 | `document_number_sequences` | System | - |
| 71 | `recurring_invoices` | Pro | - |
| 72 | `invoice_reminders` | Pro | - |
| 73 | `branches` | Pro | - |
| 74 | `user_invitations` | System | - |
| 75 | `permissions` (Spatie) | Auth | - |
| 76 | `media` (Spatie) | System | - |
| 77 | `activity_log` (Spatie) | System | - |
| 78 | `personal_access_tokens` | API | - |
| 79 | `delete_account_requests` | System | - |
| 80 | `announcements` | System | - |
| 81 | `notifications` | System | - |
| 82 | `custom_reports` | Reports | - |
| + | `discount on subscriptions` | Billing | - |

### 2.2 Modèles (60+)

Organisés en : `Billing/`, `CRM/`, `Catalog/`, `Finance/`, `Inventory/`, `Pro/`, `Purchases/`, `Reports/`, `Sales/`, `System/`, `Templates/`, `Tenancy/`

### 2.3 Contrôleurs

**Backoffice (tenant) — 40+ contrôleurs :**
- Access: RolesPermissionsController
- Billing: PlanController, SubscriptionController, SubscriptionInvoiceController
- CRM: CustomerController, CustomerAddressController, CustomerContactController
- Catalog: ProductController, ProductCategoryController, UnitController, TaxGroupController, TaxCategoryController
- Finance: BankAccountController, ExpenseController, IncomeController, LoanController, MoneyTransferController, FinanceCategoryController
- Inventory: WarehouseController, ProductStockController, StockMovementController, StockTransferController
- Pro: RecurringInvoiceController, InvoiceReminderController, BranchController, CustomReportController
- Purchases: SupplierController, PurchaseOrderController, VendorBillController, GoodsReceiptController, DebitNoteController, SupplierPaymentController
- Sales: InvoiceController, QuoteController, PaymentController, CreditNoteController, DeliveryChallanController, RefundController
- Settings: 14 controllers (company, invoice, locale, currency, barcode, appearance, email-templates, signatures, payment-methods, notifications, security, invoice-templates, plans-billings, delete-account)
- Users: UserController, UserInvitationController
- Other: DashboardController, GlobalSearchController, ExportController, NotificationController, TrashController, DocumentationController

**SuperAdmin — 8 contrôleurs :**
- DashboardController, TenantManagementController, PlanController, SubscriptionController
- TemplateCatalogController, TemplateAssignmentController, AnnouncementController
- DeleteAccountRequestController, RolesPermissionsController

---

## 3. CRUD : Ce qui existe vs ce qui manque

### ✅ CRUD Complets (routes + controller + views + requests)

| Module | Index | Create | Edit | Show | Delete | Notes |
|--------|:-----:|:------:|:----:|:----:|:------:|-------|
| Clients (CRM) | ✅ | ✅ | ✅ | ✅ | ✅ | + adresses/contacts nested |
| Produits | ✅ | ✅ | ✅ | ✅ | ✅ | + stock-in/out |
| Catégories produits | ✅ | ✅ (modal) | ✅ (modal) | - | ✅ | |
| Unités | ✅ | ✅ (modal) | ✅ (modal) | - | ✅ | |
| Tax Groups/Categories | ✅ | ✅ (modal) | ✅ (modal) | - | ✅ | |
| Factures (Invoices) | ✅ | ✅ | ✅ | ✅ | ✅ | + download/send/duplicate |
| Devis (Quotes) | ✅ | ✅ | ✅ | ✅ | ✅ | + send/convert/download |
| Paiements (Payments) | ✅ | ✅ | ✅ | ✅ | ✅ | + download |
| Avoirs (Credit Notes) | ✅ | ✅ | ✅ | ✅ | ✅ | + apply |
| Bons de livraison | ✅ | ✅ | ✅ | ✅ | ✅ | + download |
| Remboursements | ✅ | ✅ | ✅ | - | ✅ | Pas de page show |
| Fournisseurs | ✅ | ✅ | ✅ | ✅ | ✅ | |
| Bons de commande | ✅ | ✅ | ✅ | ✅ | ✅ | + receive/download |
| Factures fournisseur | ✅ | ✅ | ✅ | ✅ | ✅ | + download |
| Réceptions | ✅ | ✅ | ✅ | ✅ | ✅ | |
| Notes de débit | ✅ | ✅ | ✅ | ✅ | ✅ | + apply |
| Paiements fournisseur | ✅ | ✅ | - | - | ✅ | Pas de edit/show |
| Comptes bancaires | ✅ | ✅ | ✅ | ✅ | ✅ | |
| Dépenses | ✅ | ✅ | ✅ | - | ✅ | |
| Revenus | ✅ | ✅ | ✅ | - | ✅ | |
| Prêts | ✅ | ✅ | ✅ | ✅ | ✅ | |
| Transferts d'argent | ✅ | ✅ | ✅ | ✅ | ✅ | |
| Catégories finance | ✅ | ✅ | ✅ | - | ✅ | |
| Entrepôts | ✅ | ✅ | ✅ | ✅ | ✅ | |
| Niveaux de stock | ✅ | - | - | - | - | Index only |
| Mouvements de stock | ✅ | ✅ | - | - | - | |
| Transferts de stock | ✅ | ✅ | ✅ | ✅ | ✅ | |
| Factures récurrentes | ✅ | ✅ | ✅ | ✅ | ✅ | |
| Rappels de factures | ✅ | ✅ | ✅ | - | ✅ | |
| Succursales | ✅ | ✅ | ✅ | - | ✅ | |
| Rapports personnalisés | ✅ | ✅ | ✅ | ✅ | ✅ | |
| Utilisateurs | ✅ | - | ✅ | - | ✅ | + activate/deactivate |
| Invitations | ✅ | ✅ | - | - | ✅ | + resend/accept |
| Rôles & Permissions | ✅ | ✅ | ✅ | ✅ | ✅ | |
| Corbeille (Trash) | ✅ | - | - | - | - | restore/force-delete |

### ❌ CRUD Manquants ou Incomplets

| Module | Ce qui manque | Priorité |
|--------|---------------|:--------:|
| **Méthodes paiement fournisseur** (`supplier_payment_methods`) | Aucune route, aucun controller, aucune vue — le modèle et la migration existent mais ne sont pas exploités | 🟡 Moyenne |
| **Paiements fournisseur — Edit/Show** | Pas de vue `edit.blade.php` ni `show.blade.php` | 🟡 Moyenne |
| **Remboursements — Show** | Pas de vue `show.blade.php` | 🟢 Basse |
| **Allocations de paiement** | Pas de route dédiée — géré implicitement dans PaymentService mais pas d'UI de gestion | 🟡 Moyenne |
| **Allocations paiement fournisseur** | Idem — pas de route dédiée | 🟡 Moyenne |
| **Versements de prêts (Loan Installments)** | Pas de routes CRUD dédiées — géré dans LoanController show mais pas de paiement d'échéance | 🔴 Haute |
| **Intégrations** | Modèle + migration existent, pas de controller ni routes | 🟢 Basse (v2) |
| **Documents (file manager)** | Modèle + migration existent, pas de controller ni routes | 🟢 Basse |

---

## 4. Routes manquantes ou incomplètes

### 4.1 Backoffice — Routes existantes (17 fichiers)

| Fichier route | Lignes | Statut |
|---------------|:------:|--------|
| `dashboard.php` | 17 | ✅ Complet |
| `crm.php` | 74 | ✅ Complet |
| `catalog.php` | 133 | ✅ Complet |
| `inventory.php` | 102 | ✅ Complet |
| `sales.php` | 248 | ✅ Complet |
| `purchases.php` | 228 | ✅ Complet |
| `finance.php` | 84 | ✅ Complet |
| `pro.php` | 140 | ✅ Complet |
| `reports.php` | 55 | ✅ Complet |
| `settings.php` | 134 | ✅ Complet |
| `users.php` | 46 | ✅ Complet |
| `access.php` | 50 | ✅ Complet |
| `notifications.php` | 18 | ✅ Complet |
| `trash.php` | 24 | ✅ Complet |
| `export.php` | 15 | ✅ Complet |
| `documentation.php` | 6 | ✅ Complet |
| `auth.php` | 11 | ✅ Complet |

### 4.2 Routes manquantes

| Route manquante | Description | Priorité |
|----------------|-------------|:--------:|
| `bo.finance.loans.{loan}.installments.*` | CRUD pour payer/gérer les échéances de prêts | 🔴 |
| `bo.purchases.supplier-payment-methods.*` | CRUD pour les méthodes de paiement fournisseur | 🟡 |
| `bo.settings.integrations.*` | Gestion des intégrations tierces | 🟢 |
| `bo.documents.*` | Gestionnaire de fichiers/documents | 🟢 |
| `api/tenant/*` | API REST pour intégrations tierces | 🟡 |
| `api/webhooks/*` | Webhooks (Stripe, etc.) | 🟡 |

### 4.3 SuperAdmin — Routes existantes (8 fichiers)

| Fichier route | Statut |
|---------------|--------|
| `dashboard.php` | ✅ |
| `tenants.php` | ✅ (+ suspend/activate/usage) |
| `plans.php` | ✅ |
| `subscriptions.php` | ✅ |
| `templates.php` | ✅ (assign/revoke/catalog) |
| `settings.php` | ✅ (delete-requests) |
| `access.php` | ✅ |
| `announcements.php` | ✅ |

---

## 5. Vues (Blade) manquantes

### 5.1 Backoffice — Vues existantes (~90 fichiers)

Toutes les vues principales sont présentes. Manquent :

| Vue manquante | Raison |
|---------------|--------|
| `backoffice/sales/refunds/show.blade.php` | Pas de page détail remboursement |
| `backoffice/purchases/supplier-payments/edit.blade.php` | Pas de page modification paiement fournisseur |
| `backoffice/purchases/supplier-payments/show.blade.php` | Pas de page détail paiement fournisseur |
| `backoffice/finance/loans/installments/` | Pas de vues pour gérer les échéances |

### 5.2 Pages publiques (Landing) — MANQUANTES

Les routes dans `web.php` pointent vers des vues inexistantes :

| Route | Vue attendue | Existe ? |
|-------|-------------|:--------:|
| `/` (home) | `resources/views/web/pages/home.blade.php` | ❌ |
| `/pricing` | `resources/views/web/pages/pricing.blade.php` | ❌ |
| `/features` | `resources/views/web/pages/features.blade.php` | ❌ |
| `/contact` | `resources/views/web/pages/contact.blade.php` | ❌ |

> ⚠️ **Le dossier `resources/views/web/` n'existe pas du tout.** Toutes les routes publiques crasheront.

### 5.3 SuperAdmin — Vues existantes

Toutes présentes dans `backoffice/superadmin/` : announcements, delete-requests, template-catalog, templates, roles-permissions + dashboard.

Les vues pour tenants, plans, subscriptions utilisent `backoffice/tenants/`, `backoffice/plans/`, `backoffice/subscriptions/`.

---

## 6. Services métier — Analyse

### 6.1 Services existants (20)

| Service | Domaine | Rôle |
|---------|---------|------|
| `InvoiceService` | Sales | Création/calcul factures |
| `QuoteService` | Sales | Création/conversion devis |
| `PaymentService` | Sales | Enregistrement paiements + allocations |
| `CreditNoteService` | Sales | Création/application avoirs |
| `DeliveryChallanService` | Sales | Bons de livraison |
| `TaxCalculationService` | Sales | Calcul TVA |
| `PdfService` | Sales | Génération PDF (DomPDF) |
| `PurchaseOrderService` | Purchases | Bons de commande |
| `VendorBillService` | Purchases | Factures fournisseur |
| `GoodsReceiptService` | Purchases | Réceptions marchandises |
| `DebitNoteService` | Purchases | Notes de débit |
| `SupplierPaymentService` | Purchases | Paiements fournisseurs |
| `StockService` | Inventory | Gestion stock/mouvements |
| `ExpenseService` | Finance | Dépenses |
| `IncomeService` | Finance | Revenus |
| `CurrencyService` | Finance | Taux de change |
| `ReportService` | Reports | Rapports (ventes, achats, finance, etc.) |
| `DocumentNumberService` | System | Numérotation séquentielle |
| `ListExportService` | System | Export listes (PDF/CSV) |
| `PlanLimitService` | Billing | Vérification limites plan |
| `TenantContext` | Tenancy | Contexte tenant courant |

### 6.2 Services manquants pour production

| Service manquant | Rôle | Priorité |
|-----------------|------|:--------:|
| **`LoanService`** | Calcul échéances, paiement installments, intérêts | 🔴 |
| **`RecurringInvoiceService`** | Génération automatique des factures récurrentes (cron) | 🔴 |
| **`InvoiceReminderService`** | Envoi automatique des rappels (cron) | 🔴 |
| **`EmailService`** / Mailable classes | Envoi emails transactionnels (facture, devis, rappel) | 🔴 |
| **`NotificationService`** | Dispatch notifications (in-app + email) | 🟡 |
| **`BackupService`** | Sauvegarde base de données | 🟢 |
| **`RefundService`** | Logique remboursement (lié au payment) | 🟡 |

---

## 7. Factories manquantes (Tests)

**Existantes : 30 factories** — Couvrent les modèles principaux.

**Manquantes : 42 factories** — Principalement des modèles enfants (items, charges, allocations) et des modèles système.

<details>
<summary>Liste complète des factories manquantes</summary>

| Factory manquante | Priorité test |
|-------------------|:----:|
| DeliveryChallanFactory | 🟡 |
| DeliveryChallanItemFactory | 🟢 |
| DeliveryChallanChargeFactory | 🟢 |
| GoodsReceiptFactory | 🟡 |
| GoodsReceiptItemFactory | 🟢 |
| StockTransferFactory | 🟡 |
| StockTransferItemFactory | 🟢 |
| StockMovementFactory | 🟡 |
| ProductStockFactory | 🟡 |
| MoneyTransferFactory | 🟡 |
| LoanInstallmentFactory | 🔴 |
| CreditNoteItemFactory | 🟢 |
| CreditNoteApplicationFactory | 🟡 |
| DebitNoteItemFactory | 🟢 |
| DebitNoteApplicationFactory | 🟡 |
| PurchaseOrderItemFactory | 🟢 |
| RefundFactory | 🟡 |
| PaymentAllocationFactory | 🟡 |
| SupplierPaymentAllocationFactory | 🟡 |
| SupplierPaymentMethodFactory | 🟢 |
| InvoiceChargeFactory | 🟢 |
| QuoteItemFactory | 🟢 |
| QuoteChargeFactory | 🟢 |
| SignatureFactory | 🟢 |
| IntegrationFactory | 🟢 |
| DocumentFactory | 🟢 |
| DocumentNumberSequenceFactory | 🟡 |
| EmailLogFactory | 🟢 |
| ActivityLogFactory | 🟢 |
| NotificationLogFactory | 🟢 |
| LoginLogFactory | 🟢 |
| RecurringInvoiceFactory | 🔴 |
| InvoiceReminderFactory | 🔴 |
| BranchFactory | 🟡 |
| UserInvitationFactory | 🟡 |
| TemplateCatalogFactory | 🟢 |
| TemplatePurchaseFactory | 🟢 |
| TenantTemplateFactory | 🟢 |
| TenantTemplatePreferenceFactory | 🟢 |
| AnnouncementFactory | 🟢 |
| DeleteAccountRequestFactory | 🟢 |
| CustomReportFactory | 🟡 |

</details>

---

## 8. Emails / Notifications / Mail classes

### 8.1 Ce qui existe

**Notifications (5) :**
- `AnnouncementNotification` — annonces SuperAdmin
- `InvoiceSentNotification` — facture envoyée
- `QuoteSentNotification` — devis envoyé
- `UserInvitationNotification` — invitation utilisateur
- `VerifyEmailNotification` — vérification email

**Jobs (4) :**
- `SendInvoiceEmailJob`
- `SendQuoteEmailJob`
- `SendUserInvitationJob`
- `ExportReportJob`

**Email templates view :**
- `resources/views/emails/verify-email.blade.php` — seul template email existant

### 8.2 Ce qui manque (CRITIQUE pour production)

| Élément manquant | Description | Priorité |
|-----------------|-------------|:--------:|
| **`app/Mail/` directory** | Aucune classe Mailable ! Les jobs envoient-ils vraiment des emails ? | 🔴 |
| **Templates email pour factures** | Pas de vue email pour l'envoi de facture au client | 🔴 |
| **Templates email pour devis** | Pas de vue email pour l'envoi de devis | 🔴 |
| **Templates email pour rappels** | Pas de vue email pour les rappels de paiement | 🔴 |
| **Templates email pour paiement reçu** | Confirmation de réception paiement | 🟡 |
| **Templates email pour inscription** | Welcome email | 🟡 |
| **Templates email pour réinitialisation** | Reset password (peut utiliser celui de Laravel par défaut) | 🟢 |
| **`PaymentReceivedNotification`** | Notification quand un paiement est reçu | 🟡 |
| **`CreditNoteNotification`** | Notification avoir émis | 🟢 |
| **`InvoiceOverdueNotification`** | Notification facture en retard | 🔴 |

---

## 9. Jobs / Queues / Scheduled Tasks

### 9.1 Jobs existants

| Job | Statut |
|-----|--------|
| `SendInvoiceEmailJob` | ✅ Existe |
| `SendQuoteEmailJob` | ✅ Existe |
| `SendUserInvitationJob` | ✅ Existe |
| `ExportReportJob` | ✅ Existe |

### 9.2 Ce qui manque (CRITIQUE)

| Élément | Description | Priorité |
|---------|-------------|:--------:|
| **`app/Console/Commands/`** | Dossier vide — aucune commande Artisan personnalisée | 🔴 |
| **`GenerateRecurringInvoicesCommand`** | Commande cron pour créer les factures récurrentes | 🔴 |
| **`SendInvoiceRemindersCommand`** | Commande cron pour envoyer les rappels | 🔴 |
| **`ProcessLoanInstallmentsCommand`** | Commande pour marquer les échéances dues | 🟡 |
| **`CleanupExpiredInvitationsCommand`** | Nettoyer les invitations expirées | 🟢 |
| **`PruneActivityLogsCommand`** | Purger les anciens logs d'activité | 🟢 |
| **Schedule configuration** | `routes/console.php` ne contient que la commande `inspire` par défaut | 🔴 |

> ⚠️ **Sans commandes cron, les factures récurrentes et les rappels ne fonctionneront jamais automatiquement.**

---

## 10. API (REST)

### Statut : ❌ Non implémenté

| Fichier | Contenu |
|---------|---------|
| `routes/api/tenant.php` | Vide |
| `routes/api/webhooks.php` | Vide |

Le package `laravel/sanctum` est installé et la migration `personal_access_tokens` existe, mais aucune route API n'est définie.

### Ce qui serait nécessaire pour une version Starter

| Endpoint API | Priorité |
|-------------|:--------:|
| API CRUD Factures | 🟡 |
| API CRUD Clients | 🟡 |
| API CRUD Produits | 🟡 |
| API Paiements | 🟡 |
| Webhooks Stripe/PayPal | 🟡 |
| API Authentification (tokens) | 🟡 |

> Pour une version Starter, l'API peut être reportée à une v2, mais les webhooks de paiement sont importants si vous intégrez Stripe.

---

## 11. Frontend public / Landing pages

### Statut : ❌ Non implémenté

Les routes sont définies dans `web.php` mais les vues n'existent pas :

```
GET /          → views/web/pages/home.blade.php     ❌ N'EXISTE PAS
GET /pricing   → views/web/pages/pricing.blade.php  ❌ N'EXISTE PAS
GET /features  → views/web/pages/features.blade.php ❌ N'EXISTE PAS
GET /contact   → views/web/pages/contact.blade.php  ❌ N'EXISTE PAS
```

### Ce qui est nécessaire

| Page | Priorité |
|------|:--------:|
| Page d'accueil (landing) | 🔴 |
| Page tarification | 🔴 |
| Page fonctionnalités | 🟡 |
| Page contact | 🟡 |
| Mentions légales / CGU / CGV | 🔴 (obligatoire légalement) |
| Politique de confidentialité | 🔴 (obligatoire RGPD) |
| Layout public (header/footer) | 🔴 |

---

## 12. Portail client (Customer Portal)

### Statut : ❌ Non implémenté

Le dossier `app/Http/Controllers/frontoffice/` est vide. Aucune vue dans `resources/views/frontoffice/`.

### Ce qui serait attendu pour une version Starter SaaS

| Fonctionnalité | Priorité |
|---------------|:--------:|
| Connexion client | 🟡 |
| Voir ses factures | 🟡 |
| Voir ses devis + accepter/refuser | 🟡 |
| Payer en ligne | 🟡 |
| Télécharger PDF | 🟡 |
| Historique paiements | 🟡 |

> Le portail client peut être reporté à une v2, mais c'est un **différenciateur important** pour un SaaS de facturation.

---

## 13. Tests — Couverture actuelle

### 13.1 Tests existants (16 fichiers)

**Feature Tests :**
| Test | Couverture |
|------|------------|
| `Auth/AuthenticationTest` | Login/register |
| `Authorization/PermissionTest` | Permissions |
| `Finance/MoneyTransferControllerTest` | Transferts d'argent |
| `Settings/CompanySettingsTest` | Paramètres entreprise |
| `Settings/InvoiceTemplateSettingsTest` | Templates factures |
| `Tenancy/MassAssignmentTest` | Protection mass-assignment |
| `Tenancy/TenantIsolationTest` | Isolation tenant |
| `Users/UserInvitationTest` | Invitations |
| `Users/UserManagementTest` | Gestion utilisateurs |

**Unit Tests :**
| Test | Couverture |
|------|------------|
| `Scopes/TenantScopeTest` | Scope tenant |
| `Services/DocumentNumberServiceTest` | Numérotation |
| `Services/InvoiceServiceTest` | Service factures |
| `Services/PaymentServiceTest` | Service paiements |
| `Services/PdfServiceTest` | Génération PDF |
| `Services/ReportServiceTest` | Rapports |
| `Services/StockServiceTest` | Stock |

### 13.2 Tests manquants (par module)

| Module | Tests manquants | Priorité |
|--------|----------------|:--------:|
| CRM (Customers) | Feature test CRUD complet | 🔴 |
| Products | Feature test CRUD | 🔴 |
| Invoices | Feature test CRUD (controller) | 🔴 |
| Quotes | Feature test CRUD | 🔴 |
| Payments | Feature test CRUD | 🟡 |
| Credit Notes | Feature + Unit test | 🟡 |
| Purchases (all) | Feature test pour chaque CRUD | 🟡 |
| Expenses/Incomes | Feature test | 🟡 |
| Bank Accounts | Feature test | 🟡 |
| Loans | Feature test | 🟡 |
| Inventory | Feature test (warehouses, transfers) | 🟡 |
| SuperAdmin | Feature tests (tenants, plans, subscriptions) | 🟡 |
| Roles & Permissions | Feature test CRUD | 🟢 |
| Reports | Feature test (chaque type de rapport) | 🟢 |
| Settings | Feature test (chaque page settings) | 🟢 |

---

## 14. Sécurité & Middleware

### 14.1 Middleware existants (8) ✅

| Middleware | Rôle | Statut |
|-----------|------|--------|
| `IdentifyTenantByDomain` | Identifie le tenant par domaine | ✅ |
| `EnsureTenantIsActive` | Vérifie que le tenant est actif | ✅ |
| `SetTenantContext` | Configure le contexte tenant | ✅ |
| `EnsureActiveSubscription` | Vérifie l'abonnement actif | ✅ |
| `IsSuperAdmin` | Restreint l'accès SuperAdmin | ✅ |
| `RequirePermission` | Vérifie les permissions | ✅ |
| `CheckPlanLimit` | Vérifie les limites du plan | ✅ |
| `ContentSecurityPolicy` | Headers CSP | ✅ |

### 14.2 Sécurité — Points à vérifier

| Point | Statut | Action |
|-------|--------|--------|
| Tenant isolation (global scope) | ✅ | En place via `BelongsToTenant` trait |
| Mass assignment protection | ✅ | `tenant_id` retiré des fillable |
| CSRF protection | ✅ | Par défaut Laravel |
| CSP Headers | ✅ | Middleware en place |
| Rate limiting | ✅ | Configuré dans AppServiceProvider (login: 5/min, register: 3/min, password-reset: 3/min, pdf: 20/min, invitation: 10/min) |
| XSS prevention | ✅ | Blade échappe par défaut |
| SQL injection | ✅ | Eloquent ORM |
| File upload validation | ⚠️ | À vérifier sur chaque upload |
| 2FA | ⚠️ | Vue `two-step.blade.php` existe mais implémentation à vérifier |
| Password policy | ⚠️ | À vérifier dans `RegisterRequest` / `ResetPasswordRequest` |

---

## 15. PDF Templates — État

### 15.1 Templates gratuits (free)

| Document | Modèles | Statut |
|----------|:-------:|--------|
| Invoice | 4 | ✅ |
| Quote | 4 | ✅ |
| Credit Note | 4 | ✅ |
| Delivery Challan | 4 | ✅ |
| Purchase Order | 4 | ✅ |
| Debit Note | 1 | ⚠️ Seulement 1 modèle |
| Goods Receipt | 1 | ⚠️ Seulement 1 modèle |
| Payment Receipt | 1 | ⚠️ Seulement 1 modèle |
| Vendor Bill | 1 | ⚠️ Seulement 1 modèle |
| Supplier Payment Receipt | 1 | ⚠️ Seulement 1 modèle |

### 15.2 Templates payants (paid)

30 templates d'invoice payants disponibles (general, domain-specific, receipts).

### 15.3 Manquant

| Élément | Priorité |
|---------|:--------:|
| Templates debit note (3 modèles supplémentaires) | 🟢 |
| Templates goods receipt (3 supplémentaires) | 🟢 |
| Templates vendor bill (3 supplémentaires) | 🟢 |

---

## 16. Artisan Commands (Console)

### Statut : ❌ Aucune commande personnalisée

Le dossier `app/Console/Commands/` n'existe pas.

### Commandes nécessaires pour production

| Commande | Rôle | Priorité |
|----------|------|:--------:|
| `invoice:generate-recurring` | Générer les factures récurrentes | 🔴 |
| `invoice:send-reminders` | Envoyer les rappels de paiement | 🔴 |
| `loan:process-installments` | Traiter les échéances de prêts | 🟡 |
| `tenant:cleanup-expired` | Nettoyer les données expirées | 🟢 |
| `logs:prune` | Purger les anciens logs | 🟢 |
| `invitation:cleanup` | Nettoyer les invitations expirées | 🟢 |
| `subscription:check-expired` | Vérifier/suspendre les abonnements expirés | 🔴 |

---

## 17. Configuration / .env

### 17.1 .env.example — Points importants

| Variable | Valeur par défaut | Production |
|----------|-------------------|------------|
| `QUEUE_CONNECTION` | `database` | ✅ OK (Redis recommandé) |
| `CACHE_STORE` | `database` | ⚠️ Redis recommandé |
| `SESSION_DRIVER` | `database` | ⚠️ Redis recommandé |
| `MAIL_MAILER` | `log` | ❌ Doit être `smtp` ou service |
| `APP_DEBUG` | `true` | ❌ `false` en production |

### 17.2 Packages installés

| Package | Rôle | Statut |
|---------|------|--------|
| `laravel/framework` 12.x | Core | ✅ |
| `laravel/sanctum` | API tokens | ✅ (non utilisé encore) |
| `spatie/laravel-permission` | Rôles/Permissions | ✅ |
| `spatie/laravel-medialibrary` | Upload fichiers | ✅ |
| `spatie/laravel-activitylog` | Logs d'activité | ✅ |
| `barryvdh/laravel-dompdf` | Génération PDF | ✅ |

### 17.3 Packages manquants recommandés

| Package | Rôle | Priorité |
|---------|------|:--------:|
| `laravel/horizon` | Dashboard queues (si Redis) | 🟡 |
| `spatie/laravel-backup` | Backup BDD | 🟡 |
| `intervention/image` | Traitement images (logo, avatar) | 🟢 |
| `maatwebsite/excel` | Export Excel | 🟡 |
| `stripe/stripe-php` ou gateway | Paiement en ligne | 🟡 (si customer portal) |

---

## 18. Checklist production

### 🔴 Bloquants (MUST FIX avant production)

- [ ] **Créer les vues landing pages** (`web/pages/home`, `pricing`, etc.) — sinon les routes publiques crashent
- [ ] **Créer les commandes Artisan cron** — factures récurrentes + rappels + abonnements expirés
- [ ] **Configurer le scheduler** dans `routes/console.php`
- [ ] **Créer les Mailable classes** et templates emails pour factures/devis
- [ ] **Créer les échéances de prêts (loan installments)** — routes + UI + service
- [x] ~~Rate limiting~~ — Déjà configuré dans AppServiceProvider (login, register, password-reset, pdf, invitation)
- [ ] **Pages légales** : CGU, CGV, Politique de confidentialité
- [ ] **Tester le flow complet** : inscription → création tenant → abonnement → utilisation

### 🟡 Importants (devrait être fait)

- [ ] Ajouter les factories manquantes (au moins pour les modèles principaux)
- [ ] Écrire les Feature tests pour chaque CRUD principal
- [ ] Implémenter les routes API REST de base
- [ ] Supplier payment methods — routes + controller + vue
- [ ] Compléter les vues manquantes (refund show, supplier payment edit/show)
- [ ] Configurer Redis pour cache/session/queue en production
- [ ] Export Excel (maatwebsite/excel)
- [ ] Vérifier que TOUS les événements ont leurs listeners

### 🟢 Nice to have (v2)

- [ ] Portail client (customer portal)
- [ ] Intégrations tierces (Stripe, PayPal, etc.)
- [ ] Document manager
- [ ] Plus de templates PDF
- [ ] Système de backup automatique
- [ ] Dashboard analytics avancé
- [ ] Multi-langue (actuellement FR uniquement)
- [ ] API complète pour intégrations

---

## 19. Priorités recommandées

### Phase A — Critique (1-2 semaines)

1. **Landing pages** — Home, Pricing, Contact + layout public
2. **Commandes cron** — RecurringInvoice, Reminders, SubscriptionExpiry
3. **Système email** — Mailable classes + templates (facture, devis, rappel, welcome)
4. **Loan installments** — Routes + controller + vues + service
5. **Pages légales** — CGU, CGV, Confidentialité

### Phase B — Important (2-3 semaines)

6. **Tests** — Feature tests pour les 5-6 CRUD principaux
7. **Factories manquantes** — Au moins les 15 plus importantes
8. **Rate limiting** — Login, register, API
9. **Supplier payment methods** — CRUD complet
10. **Vues manquantes** — refund show, supplier payment edit/show

### Phase C — Polish (3-4 semaines)

11. **API REST** — Au moins factures + clients + produits
12. **Export Excel**
13. **Webhooks** — Si intégration paiement
14. **Plus de PDF templates**
15. **Customer portal** (optionnel pour starter)

---

## Annexe — Events & Listeners

### Events existants (4)

| Event | Listener |
|-------|----------|
| `InvoiceCreated` | ? |
| `InvoicePaid` | ? |
| `PaymentReceived` | ? |
| `ExpenseCreated` | ? |

### Listener existant (1)

| Listener | Event |
|----------|-------|
| `FlushReportCacheListener` | ? |

> ⚠️ **Il y a 4 events mais seulement 1 listener.** Il faut vérifier que les events sont bien dispatchés et que les listeners sont configurés dans `EventServiceProvider` (ou auto-discovery).

### Events manquants recommandés

| Event | Quand | Priorité |
|-------|-------|:--------:|
| `QuoteAccepted` | Devis accepté par client | 🟡 |
| `QuoteRejected` | Devis refusé | 🟢 |
| `CreditNoteApplied` | Avoir appliqué sur facture | 🟡 |
| `SubscriptionExpired` | Abonnement expiré | 🔴 |
| `TenantCreated` | Nouveau tenant créé | 🟡 |
| `UserRegistered` | Nouvel utilisateur inscrit | 🟡 |

---

> **Ce document est un snapshot au 2026-03-12. Il doit être mis à jour au fur et à mesure des développements.**
