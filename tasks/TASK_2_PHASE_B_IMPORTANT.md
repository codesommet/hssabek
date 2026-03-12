# TASK 2 — Phase B : Corrections Importantes (Qualité & Complétude)

**Priorité :** 🟡 IMPORTANT
**Estimation :** 2-3 semaines
**Objectif :** Compléter les CRUD incomplets, ajouter les tests, créer les factories manquantes, et solidifier la qualité globale.

---

## Table des matières

1. [CRUD Incomplets — Vues & Controllers manquants](#1-crud-incomplets--vues--controllers-manquants)
2. [Supplier Payment Methods — CRUD complet](#2-supplier-payment-methods--crud-complet)
3. [Factories manquantes (priorité haute)](#3-factories-manquantes-priorité-haute)
4. [Feature Tests — CRUD principaux](#4-feature-tests--crud-principaux)
5. [Notifications manquantes](#5-notifications-manquantes)
6. [RefundService manquant](#6-refundservice-manquant)
7. [Configuration production (Redis, etc.)](#7-configuration-production-redis-etc)
8. [Export Excel](#8-export-excel)

---

## 1. CRUD Incomplets — Vues & Controllers manquants

### 1.1 Remboursements (Refunds) — Page Show manquante

**Problème :** Le CRUD Refund existe mais il n'y a pas de page `show.blade.php`.

- [ ] **1.1.1** Créer `resources/views/backoffice/sales/refunds/show.blade.php`
  - Basé sur `invoice-details.blade.php` (template de référence pour les pages show)
  - Afficher : numéro, date, facture liée, client, montant, méthode, statut, notes
  - Lien vers la facture associée
  - Bouton retour vers la liste des remboursements
- [ ] **1.1.2** Ajouter la route `show` dans `routes/backoffice/sales.php` si manquante
- [ ] **1.1.3** Ajouter la méthode `show()` dans `RefundController` si manquante

### 1.2 Paiements Fournisseur — Edit & Show manquants

**Problème :** Le CRUD SupplierPayment n'a ni page `edit` ni page `show`.

- [ ] **1.2.1** Créer `resources/views/backoffice/purchases/supplier-payments/edit.blade.php`
  - Basé sur `edit-invoice.blade.php` ou formulaire de paiement existant
  - Champs : fournisseur (readonly), montant, date, méthode de paiement, référence, notes
  - Validation avec `@error` + `is-invalid`
  - Boutons : Enregistrer / Annuler
- [ ] **1.2.2** Créer `resources/views/backoffice/purchases/supplier-payments/show.blade.php`
  - Basé sur `invoice-details.blade.php`
  - Afficher : numéro, fournisseur, montant, date, méthode, statut, allocations sur factures fournisseur
  - Tableau des allocations (si applicable)
- [ ] **1.2.3** Ajouter les routes `edit`, `update`, `show` dans `routes/backoffice/purchases.php` si manquantes
- [ ] **1.2.4** Ajouter les méthodes `edit()`, `update()`, `show()` dans `SupplierPaymentController`
- [ ] **1.2.5** Créer `UpdateSupplierPaymentRequest.php` si manquant

### 1.3 Allocations de paiement — UI de gestion

**Problème :** Les allocations de paiement (client et fournisseur) sont gérées implicitement dans les services mais il n'y a aucune UI pour voir/gérer les allocations.

- [ ] **1.3.1** Ajouter un tableau d'allocations dans la page `show` des paiements
  - Afficher : facture allouée, montant alloué, date d'allocation
  - Permettre la suppression d'une allocation (si le paiement n'est pas verrouillé)
- [ ] **1.3.2** Ajouter le même tableau dans la page `show` des paiements fournisseur
- [ ] **1.3.3** Dans la page `show` des factures, afficher la liste des paiements/allocations reçus

---

## 2. Supplier Payment Methods — CRUD complet

### Problème
Le modèle `SupplierPaymentMethod` et la migration existent mais il n'y a **aucune route, aucun controller, aucune vue**.

### Fichiers à créer

```
app/Http/Controllers/Backoffice/Purchases/SupplierPaymentMethodController.php
app/Http/Requests/Purchases/StoreSupplierPaymentMethodRequest.php
app/Http/Requests/Purchases/UpdateSupplierPaymentMethodRequest.php
resources/views/backoffice/purchases/supplier-payment-methods/
├── index.blade.php
└── _form-modal.blade.php          ← Modal create/edit (pattern existant pour les entités simples)
```

### Tâches détaillées

- [ ] **2.1** Ajouter les routes dans `routes/backoffice/purchases.php`
  ```php
  Route::resource('supplier-payment-methods', SupplierPaymentMethodController::class)
      ->except(['show'])
      ->names('bo.purchases.supplier-payment-methods');
  ```

- [ ] **2.2** Créer `SupplierPaymentMethodController.php`
  - `index()` : liste avec recherche/filtrage
  - `store()` : création via modal
  - `update()` : modification via modal
  - `destroy()` : suppression (vérifier qu'aucun paiement n'utilise cette méthode)

- [ ] **2.3** Créer les Form Requests
  - Validation : `name` (required, unique par tenant), `type` (required, in:bank_transfer,check,cash,card,other), `details` (nullable), `is_active` (boolean)
  - Messages en français

- [ ] **2.4** Créer `index.blade.php`
  - Basé sur le template des entités simples avec modals (comme `units.blade.php` ou `category.blade.php`)
  - Tableau : Nom, Type, Détails, Statut (actif/inactif), Actions (Modifier/Supprimer)
  - Bouton "Ajouter une méthode" en haut à droite
  - Modal avec formulaire create/edit
  - `@empty` state

- [ ] **2.5** Ajouter la permission `purchases.supplier-payment-methods.view/create/edit/delete` dans `PermissionSeeder`
- [ ] **2.6** Ajouter le lien dans la sidebar (sous la section Achats)

---

## 3. Factories manquantes (priorité haute)

### Problème
42 factories manquantes. On se concentre ici sur les **15 plus importantes** pour les tests.

### Tâches détaillées

- [ ] **3.1** Créer les factories critiques (nécessaires pour les tests Phase B) :

| # | Factory | Modèle | Priorité |
|---|---------|--------|:--------:|
| 1 | `LoanInstallmentFactory` | `Finance\LoanInstallment` | 🔴 |
| 2 | `RecurringInvoiceFactory` | `Pro\RecurringInvoice` | 🔴 |
| 3 | `InvoiceReminderFactory` | `Pro\InvoiceReminder` | 🔴 |
| 4 | `RefundFactory` | `Sales\Refund` | 🟡 |
| 5 | `DeliveryChallanFactory` | `Sales\DeliveryChallan` | 🟡 |
| 6 | `GoodsReceiptFactory` | `Purchases\GoodsReceipt` | 🟡 |
| 7 | `StockTransferFactory` | `Inventory\StockTransfer` | 🟡 |
| 8 | `StockMovementFactory` | `Inventory\StockMovement` | 🟡 |
| 9 | `ProductStockFactory` | `Inventory\ProductStock` | 🟡 |
| 10 | `MoneyTransferFactory` | `Finance\MoneyTransfer` | 🟡 |
| 11 | `PaymentAllocationFactory` | `Sales\PaymentAllocation` | 🟡 |
| 12 | `CreditNoteApplicationFactory` | `Sales\CreditNoteApplication` | 🟡 |
| 13 | `DebitNoteApplicationFactory` | `Purchases\DebitNoteApplication` | 🟡 |
| 14 | `BranchFactory` | `Pro\Branch` | 🟡 |
| 15 | `UserInvitationFactory` | `System\UserInvitation` | 🟡 |

- [ ] **3.2** Pour chaque factory :
  - Vérifier les colonnes réelles dans la migration (pas les noms supposés !)
  - S'assurer que les relations `tenant_id`, `customer_id`, etc. utilisent des factories existantes
  - Ajouter `HasFactory` trait au modèle si pas déjà présent
  - Tester que `Model::factory()->create()` fonctionne sans erreur

- [ ] **3.3** Créer les factories secondaires (items, charges) :

| # | Factory | Modèle |
|---|---------|--------|
| 16 | `InvoiceChargeFactory` | `Sales\InvoiceCharge` |
| 17 | `QuoteItemFactory` | `Sales\QuoteItem` |
| 18 | `QuoteChargeFactory` | `Sales\QuoteCharge` |
| 19 | `CreditNoteItemFactory` | `Sales\CreditNoteItem` |
| 20 | `DebitNoteItemFactory` | `Purchases\DebitNoteItem` |
| 21 | `PurchaseOrderItemFactory` | `Purchases\PurchaseOrderItem` |
| 22 | `DeliveryChallanItemFactory` | `Sales\DeliveryChallanItem` |
| 23 | `GoodsReceiptItemFactory` | `Purchases\GoodsReceiptItem` |
| 24 | `StockTransferItemFactory` | `Inventory\StockTransferItem` |
| 25 | `DeliveryChallanChargeFactory` | `Sales\DeliveryChallanCharge` |

---

## 4. Feature Tests — CRUD principaux

### Problème
Seulement 16 fichiers de tests. Les CRUD principaux (Clients, Produits, Factures, Devis, etc.) n'ont pas de Feature tests.

### Convention de tests (basée sur Phase 3)
- Utiliser `createTenantWithAdmin()` helper
- Utiliser `URL::forceRootUrl('http://' . $domain)` pour le domaine tenant
- Vérifier la subscription active (middleware `EnsureActiveSubscription`)
- Tester : index, create, store, edit, update, destroy + permissions

### Tâches détaillées

- [ ] **4.1** Créer `tests/Feature/CRM/CustomerControllerTest.php`
  - test_index_lists_customers
  - test_create_shows_form
  - test_store_creates_customer
  - test_store_validates_required_fields
  - test_edit_shows_form_with_data
  - test_update_modifies_customer
  - test_destroy_soft_deletes_customer
  - test_unauthorized_user_cannot_access (permission check)
  - test_tenant_isolation (cannot see other tenant's customers)

- [ ] **4.2** Créer `tests/Feature/Catalog/ProductControllerTest.php`
  - Mêmes patterns que Customer + test stock-in/stock-out

- [ ] **4.3** Créer `tests/Feature/Sales/InvoiceControllerTest.php`
  - CRUD tests + test_download_pdf + test_send_invoice + test_duplicate_invoice
  - Tester les calculs (sous-total, taxes, total)

- [ ] **4.4** Créer `tests/Feature/Sales/QuoteControllerTest.php`
  - CRUD tests + test_convert_to_invoice + test_send_quote

- [ ] **4.5** Créer `tests/Feature/Sales/PaymentControllerTest.php`
  - CRUD tests + test_payment_allocates_to_invoice

- [ ] **4.6** Créer `tests/Feature/Sales/CreditNoteControllerTest.php`
  - CRUD tests + test_apply_to_invoice

- [ ] **4.7** Créer `tests/Feature/Purchases/SupplierControllerTest.php`
  - CRUD tests standard

- [ ] **4.8** Créer `tests/Feature/Purchases/PurchaseOrderControllerTest.php`
  - CRUD tests + test_receive_goods

- [ ] **4.9** Créer `tests/Feature/Finance/BankAccountControllerTest.php`
  - CRUD tests

- [ ] **4.10** Créer `tests/Feature/Finance/ExpenseControllerTest.php`
  - CRUD tests + vérifier catégorie finance

- [ ] **4.11** Créer `tests/Feature/Finance/LoanControllerTest.php`
  - CRUD tests + test installments generation

- [ ] **4.12** Créer `tests/Feature/Inventory/WarehouseControllerTest.php`
  - CRUD tests

- [ ] **4.13** Créer `tests/Feature/SuperAdmin/TenantManagementTest.php`
  - test_list_tenants, test_suspend_tenant, test_activate_tenant

- [ ] **4.14** Créer `tests/Feature/SuperAdmin/PlanManagementTest.php`
  - CRUD tests pour les plans

---

## 5. Notifications manquantes

### Problème
Des events existent mais pas de notifications/listeners correspondantes pour certains cas.

### Tâches détaillées

- [ ] **5.1** Créer `app/Notifications/PaymentReceivedNotification.php`
  - Canal : `database` + `mail` (optionnel)
  - Données : montant, facture liée, date
  - Dispatché quand un paiement est reçu

- [ ] **5.2** Créer `app/Notifications/InvoiceOverdueNotification.php`
  - Canal : `database` + `mail`
  - Données : facture, montant dû, jours de retard
  - Dispatché par `SendInvoiceRemindersCommand`

- [ ] **5.3** Créer `app/Notifications/SubscriptionExpiringNotification.php`
  - Canal : `database` + `mail`
  - Données : nom du plan, date d'expiration
  - Dispatché 7 jours avant expiration

- [ ] **5.4** Créer `app/Notifications/CreditNoteIssuedNotification.php`
  - Canal : `database`
  - Données : numéro avoir, montant, facture liée

- [ ] **5.5** Vérifier que `NotificationController` dans le backoffice gère bien l'affichage et le marquage comme lu des notifications database

---

## 6. RefundService manquant

### Problème
Le `RefundController` existe mais il n'y a pas de service métier dédié. La logique est probablement dans le controller.

### Tâches détaillées

- [ ] **6.1** Créer `app/Services/Sales/RefundService.php`
  - `createRefund(array $data)` : crée le remboursement + met à jour le statut de la facture si entièrement remboursée
  - `processRefund(Refund $refund)` : marque comme traité, met à jour le solde
  - `cancelRefund(Refund $refund)` : annule et restaure le solde
  - Interaction avec `PaymentService` pour ajuster les allocations

- [ ] **6.2** Refactorer `RefundController` pour utiliser `RefundService`
  - Déplacer la logique métier du controller vers le service
  - Le controller ne doit gérer que la validation, l'appel au service, et la réponse

---

## 7. Configuration production (Redis, etc.)

### Problème
Les drivers cache/session/queue sont sur `database`. En production, Redis est fortement recommandé.

### Tâches détaillées

- [ ] **7.1** Mettre à jour `.env.example` avec les variables Redis :
  ```env
  REDIS_HOST=127.0.0.1
  REDIS_PASSWORD=null
  REDIS_PORT=6379

  CACHE_STORE=redis
  SESSION_DRIVER=redis
  QUEUE_CONNECTION=redis
  ```

- [ ] **7.2** Vérifier que `config/database.php` a la configuration Redis correcte
- [ ] **7.3** Vérifier que `predis/predis` ou `phpredis` extension est dans les dépendances
- [ ] **7.4** Documenter les deux modes (database pour dev, Redis pour production)

- [ ] **7.5** Vérifier la configuration `.env.example` complète :
  - `APP_DEBUG=false` en production
  - `MAIL_MAILER=smtp` (pas `log`)
  - `APP_URL` correctement configuré
  - `FILESYSTEM_DISK` configuré

- [ ] **7.6** Vérifier les fichiers de config Laravel :
  - `config/queue.php` — retry_after, max_tries
  - `config/mail.php` — configuration SMTP
  - `config/logging.php` — canal production (daily au lieu de stack)

---

## 8. Export Excel

### Problème
`ListExportService` gère l'export PDF/CSV mais pas Excel. Pour un SaaS de facturation, l'export Excel est très demandé.

### Tâches détaillées

- [ ] **8.1** Installer `maatwebsite/excel` :
  ```bash
  composer require maatwebsite/excel
  ```

- [ ] **8.2** Créer les classes d'export :

```
app/Exports/
├── InvoicesExport.php
├── CustomersExport.php
├── ProductsExport.php
├── PaymentsExport.php
├── ExpensesExport.php
└── SuppliersExport.php
```

- [ ] **8.3** Pour chaque export :
  - Implémenter `FromQuery`, `WithHeadings`, `WithMapping`
  - Respecter l'isolation tenant (scope la query au tenant courant)
  - Headers en français
  - Formatage des montants, dates, statuts

- [ ] **8.4** Ajouter un bouton "Exporter Excel" dans les pages index existantes
  - À côté du bouton "Exporter PDF" existant
  - Même pattern UI que l'export PDF

- [ ] **8.5** Ajouter les routes d'export dans `routes/backoffice/export.php`
  ```php
  Route::get('export/{type}/excel', [ExportController::class, 'excel'])->name('bo.export.excel');
  ```

- [ ] **8.6** Mettre à jour `ExportController` pour gérer le format Excel

---

## Résumé des fichiers à créer/modifier

### Nouveaux fichiers (~45+)

| Catégorie | Nombre | Fichiers |
|-----------|:------:|----------|
| Vues Blade | 4 | refund show, supplier-payment edit/show, supplier-payment-methods index |
| Controllers | 2 | SupplierPaymentMethodController, LoanInstallmentController update |
| Form Requests | 3 | StoreSupplierPaymentMethodRequest, UpdateSupplierPaymentMethodRequest, UpdateSupplierPaymentRequest |
| Services | 1 | RefundService |
| Factories | 25 | Voir section 3 |
| Tests | 14 | Voir section 4 |
| Notifications | 4 | Voir section 5 |
| Exports | 6 | Voir section 8 |

### Fichiers à modifier (~10)

| Fichier | Modification |
|---------|-------------|
| `routes/backoffice/purchases.php` | Routes supplier-payment-methods + supplier-payment edit/show |
| `routes/backoffice/sales.php` | Route refund show |
| `routes/backoffice/export.php` | Routes export Excel |
| `SupplierPaymentController.php` | Ajouter edit/update/show |
| `RefundController.php` | Ajouter show + refactorer avec RefundService |
| `ExportController.php` | Ajouter méthode excel() |
| `.env.example` | Variables Redis + SMTP |
| `database/seeders/PermissionSeeder.php` | Permissions supplier-payment-methods |
| `composer.json` | Ajouter maatwebsite/excel |

---

> **Critère de succès :** Tous les CRUD ont leurs vues complètes, les tests passent pour les modules principaux, les factories couvrent tous les modèles importants, et l'export Excel fonctionne.
