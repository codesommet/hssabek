# TASK 1 — Phase A : Corrections Critiques (Bloquants Production)

**Priorité :** 🔴 CRITIQUE
**Estimation :** 1-2 semaines
**Objectif :** Corriger tout ce qui fait crasher l'application ou empêche une mise en production.

---

## Table des matières

1. [Landing Pages (Site public)](#1-landing-pages-site-public)
2. [Commandes Artisan & Cron](#2-commandes-artisan--cron)
3. [Système Email (Mailable + Templates)](#3-système-email-mailable--templates)
4. [Loan Installments (Échéances de prêts)](#4-loan-installments-échéances-de-prêts)
5. [Pages Légales](#5-pages-légales)
6. [Events & Listeners manquants](#6-events--listeners-manquants)
7. [Scheduler Configuration](#7-scheduler-configuration)

---

## 1. Landing Pages (Site public)

### Problème
Les routes `GET /`, `/pricing`, `/features`, `/contact` sont définies dans `routes/web.php` mais les vues n'existent pas. **Toute visite sur ces URLs crashe l'application.**

### Fichiers à créer

```
resources/views/web/
├── layout/
│   ├── app.blade.php              ← Layout principal public (header, footer, nav)
│   ├── header.blade.php           ← Navigation publique
│   └── footer.blade.php           ← Footer avec liens légaux
└── pages/
    ├── home.blade.php             ← Page d'accueil / Landing page
    ├── pricing.blade.php          ← Page tarification (afficher les Plans depuis DB)
    ├── features.blade.php         ← Page fonctionnalités
    └── contact.blade.php          ← Formulaire de contact
```

### Tâches détaillées

- [ ] **1.1** Créer le dossier `resources/views/web/layout/`
- [ ] **1.2** Créer `app.blade.php` — layout public avec :
  - Header/navbar avec liens : Accueil, Fonctionnalités, Tarification, Contact, Connexion, Inscription
  - Footer avec liens légaux (CGU, CGV, Confidentialité)
  - Inclusion des assets CSS/JS du thème
- [ ] **1.3** Créer `home.blade.php` — page d'accueil :
  - Hero section avec CTA "Commencer gratuitement" / "Voir les tarifs"
  - Section fonctionnalités principales (3-4 blocs)
  - Section témoignages (statique)
  - Section CTA final
- [ ] **1.4** Créer `pricing.blade.php` — page tarification :
  - Charger les plans depuis `Plan::where('is_active', true)->get()`
  - Afficher les cartes de tarification avec features/limites
  - Bouton "S'inscrire" par plan → redirige vers `/register?plan={slug}`
- [ ] **1.5** Créer `features.blade.php` — page fonctionnalités :
  - Liste des fonctionnalités organisées par catégorie (Facturation, CRM, Stock, Achats, etc.)
  - Screenshots ou icônes illustratives
- [ ] **1.6** Créer `contact.blade.php` — formulaire contact :
  - Formulaire : nom, email, sujet, message
  - Créer `ContactRequest` pour validation
  - Créer `ContactController@store` (ou ajouter à un controller existant)
  - Envoyer un email à l'admin avec les données du formulaire
- [ ] **1.7** Mettre à jour `routes/web.php` pour pointer vers un controller (au lieu de closures ou vues directes)
- [ ] **1.8** Créer `app/Http/Controllers/Web/PageController.php` avec méthodes : `home()`, `pricing()`, `features()`, `contact()`

### Notes
- Utiliser les composants Bootstrap du thème existant (pas de nouveau framework CSS)
- Tous les textes en **français**
- La page pricing doit être dynamique (données des Plans depuis la DB)

---

## 2. Commandes Artisan & Cron

### Problème
Le dossier `app/Console/Commands/` n'existe pas. Aucune commande cron n'est configurée. **Les factures récurrentes et rappels ne fonctionnent pas.**

### Fichiers à créer

```
app/Console/Commands/
├── GenerateRecurringInvoicesCommand.php
├── SendInvoiceRemindersCommand.php
├── CheckExpiredSubscriptionsCommand.php
└── ProcessLoanInstallmentsCommand.php
```

### Tâches détaillées

- [ ] **2.1** Créer `GenerateRecurringInvoicesCommand.php`
  - Signature : `invoice:generate-recurring`
  - Logique :
    1. Récupérer toutes les `RecurringInvoice` actives (`status = 'active'`) dont `next_invoice_date <= today()`
    2. Pour chaque récurrence, créer une nouvelle `Invoice` via `InvoiceService`
    3. Mettre à jour `next_invoice_date` selon la fréquence (daily/weekly/monthly/yearly)
    4. Mettre à jour `last_generated_at`
    5. Incrémenter `invoices_generated`
    6. Si `end_date` atteinte ou `max_invoices` atteint → passer en `completed`
    7. Logger chaque facture créée
  - Créer `RecurringInvoiceService` si nécessaire

- [ ] **2.2** Créer `SendInvoiceRemindersCommand.php`
  - Signature : `invoice:send-reminders`
  - Logique :
    1. Récupérer les `InvoiceReminder` actifs dont `next_send_date <= today()`
    2. Pour chaque rappel, vérifier que la facture est toujours impayée
    3. Envoyer l'email de rappel via la Mailable appropriée (voir section 3)
    4. Mettre à jour `last_sent_at`, incrémenter `times_sent`
    5. Calculer `next_send_date` selon `frequency_days`
    6. Si `max_reminders` atteint → désactiver le rappel
    7. Logger dans `email_logs`

- [ ] **2.3** Créer `CheckExpiredSubscriptionsCommand.php`
  - Signature : `subscription:check-expired`
  - Logique :
    1. Récupérer les `Subscription` où `status = 'active'` et `ends_at < now()`
    2. Passer le statut à `expired`
    3. Optionnel : envoyer une notification au tenant
    4. Logger l'action

- [ ] **2.4** Créer `ProcessLoanInstallmentsCommand.php`
  - Signature : `loan:process-installments`
  - Logique :
    1. Récupérer les `LoanInstallment` où `status = 'pending'` et `due_date <= today()`
    2. Passer le statut à `overdue`
    3. Optionnel : envoyer une notification
    4. Logger

### Dépendances
- Tâche 3 (Mailable classes) pour l'envoi d'emails dans les rappels
- Tâche 4 (Loan installments) pour la commande `loan:process-installments`

---

## 3. Système Email (Mailable + Templates)

### Problème
Le dossier `app/Mail/` est **vide**. Les jobs `SendInvoiceEmailJob` et `SendQuoteEmailJob` existent mais n'ont probablement pas de Mailable class à utiliser. **Aucun email transactionnel ne peut être envoyé.**

### Fichiers à créer

```
app/Mail/
├── InvoiceMail.php
├── QuoteMail.php
├── PaymentReceivedMail.php
├── InvoiceReminderMail.php
├── WelcomeMail.php
└── ContactFormMail.php

resources/views/emails/
├── layout.blade.php              ← Layout email commun (header logo, footer)
├── invoice.blade.php             ← Template email facture
├── quote.blade.php               ← Template email devis
├── payment-received.blade.php    ← Template confirmation paiement
├── invoice-reminder.blade.php    ← Template rappel de paiement
├── welcome.blade.php             ← Template bienvenue après inscription
└── contact-form.blade.php        ← Template message contact reçu
```

### Tâches détaillées

- [ ] **3.1** Créer `resources/views/emails/layout.blade.php`
  - Layout HTML email responsive
  - Header avec logo de l'entreprise (depuis `TenantSetting`)
  - Footer avec nom entreprise, adresse, lien de désinscription
  - Styles inline (les emails ne supportent pas les CSS externes)

- [ ] **3.2** Créer `InvoiceMail.php`
  - Reçoit : `Invoice $invoice`, `Tenant $tenant`
  - Sujet : "Facture {numéro} — {nom entreprise}"
  - Attache le PDF de la facture (via `PdfService`)
  - Vue : `emails.invoice`
  - Contenu : numéro facture, montant, date d'échéance, lien de paiement (si portail client)

- [ ] **3.3** Créer `QuoteMail.php`
  - Reçoit : `Quote $quote`, `Tenant $tenant`
  - Sujet : "Devis {numéro} — {nom entreprise}"
  - Attache le PDF du devis
  - Vue : `emails.quote`
  - Contenu : numéro devis, montant, date de validité

- [ ] **3.4** Créer `PaymentReceivedMail.php`
  - Reçoit : `Payment $payment`, `Invoice $invoice`
  - Sujet : "Paiement reçu — Facture {numéro}"
  - Vue : `emails.payment-received`
  - Contenu : montant payé, solde restant

- [ ] **3.5** Créer `InvoiceReminderMail.php`
  - Reçoit : `Invoice $invoice`, `InvoiceReminder $reminder`
  - Sujet : "Rappel — Facture {numéro} en attente de paiement"
  - Vue : `emails.invoice-reminder`
  - Contenu : numéro facture, montant dû, jours de retard, lien de paiement

- [ ] **3.6** Créer `WelcomeMail.php`
  - Reçoit : `User $user`, `Tenant $tenant`
  - Sujet : "Bienvenue sur {nom plateforme}"
  - Vue : `emails.welcome`
  - Contenu : message de bienvenue, premiers pas, lien vers le dashboard

- [ ] **3.7** Créer `ContactFormMail.php`
  - Reçoit : données du formulaire contact (nom, email, sujet, message)
  - Sujet : "Nouveau message de contact — {sujet}"
  - Vue : `emails.contact-form`

- [ ] **3.8** Créer les 6 templates email Blade correspondants
  - Tous doivent `@extend` le layout email
  - Textes en **français**
  - Design professionnel et responsive

- [ ] **3.9** Mettre à jour `SendInvoiceEmailJob` pour utiliser `InvoiceMail`
  - Vérifier que le job dispatch correctement le Mailable
  - S'assurer que le PDF est attaché

- [ ] **3.10** Mettre à jour `SendQuoteEmailJob` pour utiliser `QuoteMail`

- [ ] **3.11** Configurer `.env.example` avec des valeurs SMTP réalistes
  - `MAIL_MAILER=smtp`
  - `MAIL_FROM_ADDRESS` et `MAIL_FROM_NAME`

---

## 4. Loan Installments (Échéances de prêts)

### Problème
Le modèle `LoanInstallment` et la migration existent mais il n'y a **aucune route, aucun controller dédié, et aucune UI** pour gérer les échéances de prêts. On ne peut pas marquer une échéance comme payée.

### Fichiers à créer

```
app/Services/Finance/LoanService.php
app/Http/Controllers/Backoffice/Finance/LoanInstallmentController.php
app/Http/Requests/Finance/PayLoanInstallmentRequest.php
resources/views/backoffice/finance/loans/installments/
├── index.blade.php                ← Liste des échéances d'un prêt
└── _pay-modal.blade.php           ← Modal de paiement d'échéance
```

### Tâches détaillées

- [ ] **4.1** Créer `LoanService.php`
  - `calculateInstallments(Loan $loan)` : calcule le tableau d'amortissement
  - `payInstallment(LoanInstallment $installment, array $data)` : marque comme payé, met à jour le solde du prêt
  - `getOverdueInstallments(int $tenantId)` : retourne les échéances en retard
  - `generateInstallmentSchedule(Loan $loan)` : crée les `LoanInstallment` records

- [ ] **4.2** Créer les routes dans `routes/backoffice/finance.php`
  ```php
  Route::prefix('loans/{loan}/installments')->name('bo.finance.loans.installments.')->group(function () {
      Route::get('/', [LoanInstallmentController::class, 'index'])->name('index');
      Route::post('{installment}/pay', [LoanInstallmentController::class, 'pay'])->name('pay');
      Route::post('{installment}/cancel', [LoanInstallmentController::class, 'cancel'])->name('cancel');
  });
  ```

- [ ] **4.3** Créer `LoanInstallmentController.php`
  - `index(Loan $loan)` : affiche la liste des échéances avec statuts
  - `pay(PayLoanInstallmentRequest $request, Loan $loan, LoanInstallment $installment)` : enregistre le paiement
  - `cancel(Loan $loan, LoanInstallment $installment)` : annule une échéance payée

- [ ] **4.4** Créer `PayLoanInstallmentRequest.php`
  - Validation : `payment_date` (required, date), `amount` (required, numeric, min:0), `payment_method` (required), `notes` (nullable)

- [ ] **4.5** Créer la vue `index.blade.php`
  - Basée sur le template de référence (table avec statuts)
  - Colonnes : # échéance, date d'échéance, montant principal, intérêts, montant total, statut, actions
  - Statuts colorés : En attente (jaune), Payé (vert), En retard (rouge), Annulé (gris)
  - Bouton "Payer" ouvre une modal

- [ ] **4.6** Créer la modal de paiement `_pay-modal.blade.php`
  - Champs : date de paiement, montant (pré-rempli), méthode de paiement, notes
  - Validation côté client + serveur

- [ ] **4.7** Mettre à jour la vue `backoffice/finance/loans/show.blade.php`
  - Ajouter un tableau récapitulatif des échéances sous les détails du prêt
  - Lien "Voir toutes les échéances" vers la page complète

- [ ] **4.8** Mettre à jour `LoanController@store` et `@update`
  - Après création/modification d'un prêt, générer automatiquement les échéances via `LoanService::generateInstallmentSchedule()`

---

## 5. Pages Légales

### Problème
Aucune page légale n'existe. **C'est obligatoire légalement (RGPD) pour un SaaS en production.**

### Fichiers à créer

```
resources/views/web/pages/
├── terms.blade.php                ← Conditions Générales d'Utilisation (CGU)
├── privacy.blade.php              ← Politique de Confidentialité
└── legal.blade.php                ← Mentions Légales
```

### Tâches détaillées

- [ ] **5.1** Ajouter les routes dans `routes/web.php`
  ```php
  Route::get('/conditions-utilisation', [PageController::class, 'terms'])->name('terms');
  Route::get('/politique-confidentialite', [PageController::class, 'privacy'])->name('privacy');
  Route::get('/mentions-legales', [PageController::class, 'legal'])->name('legal');
  ```

- [ ] **5.2** Créer `terms.blade.php` — CGU
  - Structure avec sections numérotées
  - Couvrir : objet, accès, obligations, propriété intellectuelle, résiliation, responsabilité
  - Placeholders pour les données spécifiques de l'entreprise (nom, adresse, SIRET)

- [ ] **5.3** Créer `privacy.blade.php` — Politique de confidentialité
  - Conforme RGPD
  - Sections : données collectées, finalités, durée conservation, droits (accès, rectification, suppression, portabilité), cookies, sous-traitants, contact DPO

- [ ] **5.4** Créer `legal.blade.php` — Mentions légales
  - Éditeur, hébergeur, directeur de publication, SIRET, etc.

- [ ] **5.5** Ajouter les liens dans le footer du layout public (`web/layout/footer.blade.php`)
- [ ] **5.6** Ajouter les liens dans le footer du layout backoffice si applicable

---

## 6. Events & Listeners manquants

### Problème
Il y a 4 events mais seulement 1 listener. Les events sont potentiellement dispatchés mais rien ne les écoute.

### Tâches détaillées

- [ ] **6.1** Vérifier dans `EventServiceProvider` (ou auto-discovery) que les events sont bien mappés aux listeners
- [ ] **6.2** Créer les listeners manquants :

  | Listener à créer | Event | Action |
  |-------------------|-------|--------|
  | `SendInvoiceCreatedNotification` | `InvoiceCreated` | Notifier le tenant (log activité) |
  | `UpdateInvoiceStatusOnPayment` | `InvoicePaid` | Mettre à jour le statut de la facture |
  | `SendPaymentConfirmation` | `PaymentReceived` | Envoyer email confirmation au client |
  | `UpdateFinancialReports` | `ExpenseCreated` | Flush cache rapports financiers |

- [ ] **6.3** Créer l'event `SubscriptionExpired` et son listener
  - Event dispatché par `CheckExpiredSubscriptionsCommand`
  - Listener : notifier le tenant, logger l'action

- [ ] **6.4** Vérifier que `FlushReportCacheListener` est bien attaché aux bons events (toute opération qui change les données financières)

---

## 7. Scheduler Configuration

### Problème
`routes/console.php` ne contient que la commande `inspire` par défaut.

### Tâches détaillées

- [ ] **7.1** Configurer le scheduler dans `routes/console.php` :
  ```php
  use Illuminate\Support\Facades\Schedule;

  Schedule::command('invoice:generate-recurring')->dailyAt('06:00');
  Schedule::command('invoice:send-reminders')->dailyAt('08:00');
  Schedule::command('subscription:check-expired')->dailyAt('00:30');
  Schedule::command('loan:process-installments')->dailyAt('07:00');
  ```

- [ ] **7.2** Documenter dans `.env.example` ou README que le cron système doit être configuré :
  ```
  * * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
  ```

- [ ] **7.3** Tester chaque commande manuellement avec `php artisan {command}` avant de les scheduler

---

## Résumé des fichiers à créer/modifier

### Nouveaux fichiers (~25)

| # | Fichier | Type |
|---|---------|------|
| 1 | `resources/views/web/layout/app.blade.php` | Blade layout |
| 2 | `resources/views/web/layout/header.blade.php` | Blade partial |
| 3 | `resources/views/web/layout/footer.blade.php` | Blade partial |
| 4 | `resources/views/web/pages/home.blade.php` | Blade view |
| 5 | `resources/views/web/pages/pricing.blade.php` | Blade view |
| 6 | `resources/views/web/pages/features.blade.php` | Blade view |
| 7 | `resources/views/web/pages/contact.blade.php` | Blade view |
| 8 | `resources/views/web/pages/terms.blade.php` | Blade view |
| 9 | `resources/views/web/pages/privacy.blade.php` | Blade view |
| 10 | `resources/views/web/pages/legal.blade.php` | Blade view |
| 11 | `app/Http/Controllers/Web/PageController.php` | Controller |
| 12 | `app/Http/Requests/Web/ContactRequest.php` | Form Request |
| 13 | `app/Console/Commands/GenerateRecurringInvoicesCommand.php` | Command |
| 14 | `app/Console/Commands/SendInvoiceRemindersCommand.php` | Command |
| 15 | `app/Console/Commands/CheckExpiredSubscriptionsCommand.php` | Command |
| 16 | `app/Console/Commands/ProcessLoanInstallmentsCommand.php` | Command |
| 17 | `app/Mail/InvoiceMail.php` | Mailable |
| 18 | `app/Mail/QuoteMail.php` | Mailable |
| 19 | `app/Mail/PaymentReceivedMail.php` | Mailable |
| 20 | `app/Mail/InvoiceReminderMail.php` | Mailable |
| 21 | `app/Mail/WelcomeMail.php` | Mailable |
| 22 | `app/Mail/ContactFormMail.php` | Mailable |
| 23 | `resources/views/emails/layout.blade.php` | Blade layout |
| 24 | `resources/views/emails/invoice.blade.php` | Blade view |
| 25 | `resources/views/emails/quote.blade.php` | Blade view |
| 26 | `resources/views/emails/payment-received.blade.php` | Blade view |
| 27 | `resources/views/emails/invoice-reminder.blade.php` | Blade view |
| 28 | `resources/views/emails/welcome.blade.php` | Blade view |
| 29 | `resources/views/emails/contact-form.blade.php` | Blade view |
| 30 | `app/Services/Finance/LoanService.php` | Service |
| 31 | `app/Http/Controllers/Backoffice/Finance/LoanInstallmentController.php` | Controller |
| 32 | `app/Http/Requests/Finance/PayLoanInstallmentRequest.php` | Form Request |
| 33 | `resources/views/backoffice/finance/loans/installments/index.blade.php` | Blade view |

### Fichiers à modifier (~5)

| # | Fichier | Modification |
|---|---------|-------------|
| 1 | `routes/web.php` | Ajouter routes pages légales + controller PageController |
| 2 | `routes/console.php` | Configurer le scheduler |
| 3 | `routes/backoffice/finance.php` | Ajouter routes loan installments |
| 4 | `app/Jobs/SendInvoiceEmailJob.php` | Utiliser `InvoiceMail` Mailable |
| 5 | `app/Jobs/SendQuoteEmailJob.php` | Utiliser `QuoteMail` Mailable |
| 6 | `resources/views/backoffice/finance/loans/show.blade.php` | Ajouter tableau échéances |

---

> **Critère de succès :** Toutes les routes publiques fonctionnent sans crash, les emails sont envoyés, les commandes cron génèrent les factures récurrentes, et les pages légales sont accessibles.
