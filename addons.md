# Addons System — Facturation SaaS

## Overview

This document outlines two major addon integrations planned for the platform:

1. **AI Tokens System** — Sell AI-powered features as purchasable token packs
2. **Telegram Bot Integration** — Automate document workflows via Telegram

---

## 1. AI Tokens System

### Concept

Companies purchase token packs (or get a monthly allowance based on their plan). Each AI action consumes tokens. This creates a recurring revenue stream beyond subscriptions.

### What You Need

#### Database

| Table | Key Columns | Purpose |
|-------|-------------|---------|
| `ai_token_packages` | `id`, `name`, `token_count`, `price`, `currency`, `is_active` | Define purchasable token packs (e.g. 500 tokens = 10€) |
| `ai_token_balances` | `id`, `tenant_id`, `balance`, `lifetime_purchased`, `lifetime_used` | Current token balance per tenant |
| `ai_token_transactions` | `id`, `tenant_id`, `user_id`, `type` (credit/debit), `amount`, `balance_after`, `source` (purchase/plan/admin), `ai_action_type`, `reference_id`, `metadata`, `created_at` | Full ledger of every token movement |
| `ai_action_costs` | `id`, `action_type`, `token_cost`, `model_used`, `description` | Cost table — how many tokens each AI action costs |

#### AI Actions to Sell (Examples)

| Action | Token Cost | Description |
|--------|-----------|-------------|
| `invoice.generate_from_text` | 5 | Paste text/photo, AI extracts invoice data |
| `invoice.smart_fill` | 3 | Auto-complete invoice fields from customer history |
| `quote.generate` | 5 | Generate a quote from a brief description |
| `contract.draft` | 10 | Draft a contract from parameters |
| `contract.analyze` | 8 | Analyze an uploaded contract, extract key terms |
| `email.compose` | 2 | AI-written professional email for sending invoice/quote |
| `report.insights` | 5 | AI-generated business insights from financial data |
| `ocr.extract` | 4 | Extract data from scanned document/photo |
| `product.description` | 2 | Generate product description from name/category |
| `customer.summarize` | 3 | Summarize customer activity and payment behavior |
| `expense.categorize` | 1 | Auto-categorize an expense from description/receipt |
| `translation` | 3 | Translate invoice/quote to another language |

#### Backend Architecture

```
app/
├── Models/AI/
│   ├── AiTokenPackage.php
│   ├── AiTokenBalance.php
│   ├── AiTokenTransaction.php
│   └── AiActionCost.php
├── Services/AI/
│   ├── TokenService.php           # Core: check balance, debit, credit, refund
│   ├── AiGatewayService.php       # Routes requests to Claude/OpenAI/etc.
│   ├── Actions/
│   │   ├── InvoiceExtractAction.php
│   │   ├── ContractDraftAction.php
│   │   ├── EmailComposeAction.php
│   │   ├── OcrExtractAction.php
│   │   └── ReportInsightsAction.php
│   └── Providers/
│       ├── ClaudeProvider.php      # Anthropic API integration
│       └── OpenAiProvider.php      # OpenAI fallback (optional)
├── Http/Controllers/Backoffice/AI/
│   ├── AiTokenController.php      # Buy tokens, view balance, history
│   └── AiActionController.php     # Execute AI actions
├── Http/Middleware/
│   └── CheckAiTokenBalance.php    # Middleware to verify tokens before action
└── Jobs/
    └── ProcessAiActionJob.php     # Queue heavy AI tasks
```

#### Token Service (Core Logic)

```php
class TokenService
{
    public function hasEnoughTokens(Tenant $tenant, string $actionType): bool;
    public function debit(Tenant $tenant, User $user, string $actionType, ?string $referenceId = null): AiTokenTransaction;
    public function credit(Tenant $tenant, int $amount, string $source, ?array $metadata = null): AiTokenTransaction;
    public function refund(AiTokenTransaction $transaction): AiTokenTransaction;
    public function getBalance(Tenant $tenant): int;
    public function getUsageStats(Tenant $tenant, ?Carbon $from = null, ?Carbon $to = null): array;
}
```

#### Plan Integration

Add to `plans` table:

| Column | Type | Description |
|--------|------|-------------|
| `monthly_ai_tokens` | `integer`, default `0` | Free tokens included in plan per month |
| `ai_enabled` | `boolean`, default `false` | Whether plan has access to AI features |

A scheduled job (`MonthlyTokenAllocationJob`) credits free tokens on billing cycle.

#### Payment Flow

1. Tenant goes to **Settings > AI Tokens**
2. Sees current balance + usage chart
3. Picks a token package (or auto-recharge threshold)
4. Payment processed via existing billing system
5. Tokens credited to `ai_token_balances`

#### Config (`config/ai.php`)

```php
return [
    'default_provider' => env('AI_PROVIDER', 'claude'),
    'providers' => [
        'claude' => [
            'api_key' => env('ANTHROPIC_API_KEY'),
            'model'   => env('AI_CLAUDE_MODEL', 'claude-sonnet-4-20250514'),
        ],
        'openai' => [
            'api_key' => env('OPENAI_API_KEY'),
            'model'   => env('AI_OPENAI_MODEL', 'gpt-4o'),
        ],
    ],
    'auto_recharge' => [
        'enabled'   => false,
        'threshold' => 50,   // recharge when balance drops below
        'package'   => null,  // package_id to auto-buy
    ],
];
```

#### SuperAdmin Controls

- Set token costs per action (dynamic pricing)
- Grant bonus tokens to tenants
- View global AI usage analytics
- Enable/disable AI features per plan
- Monitor API costs vs. token revenue (margin tracking)

---

## 2. Telegram Bot Integration

### Concept

A Telegram bot connected to each tenant's account. Users can interact via Telegram to:
- Extract contract/invoice data from photos
- Create invoices, quotes, expenses on the go
- Receive notifications (payment received, invoice overdue, etc.)
- Query business data (balance, pending invoices, etc.)

### What You Need

#### Database

| Table | Key Columns | Purpose |
|-------|-------------|---------|
| `telegram_connections` | `id`, `tenant_id`, `user_id`, `telegram_user_id`, `telegram_chat_id`, `bot_token`, `is_active`, `connected_at` | Link Telegram users to platform users |
| `telegram_messages` | `id`, `tenant_id`, `telegram_chat_id`, `direction` (in/out), `message_type`, `content`, `processed`, `ai_action_id`, `created_at` | Message log for audit |
| `telegram_bot_configs` | `id`, `tenant_id`, `bot_username`, `bot_token`, `webhook_secret`, `enabled_commands`, `notification_settings` | Per-tenant bot configuration |

#### Bot Commands

| Command | Description | AI Tokens |
|---------|-------------|-----------|
| `/start` | Connect Telegram account to platform | 0 |
| `/facture` | Create a quick invoice (guided flow) | 0 |
| `/devis` | Create a quick quote | 0 |
| `/depense` | Log an expense (text or photo) | 0 |
| `/scan` | Send photo → AI extracts document data | 4 (OCR) |
| `/contrat` | Draft a contract from brief | 10 |
| `/solde` | Check business balance summary | 0 |
| `/impayees` | List overdue invoices | 0 |
| `/client [name]` | Quick customer lookup | 0 |
| `/stats` | Today's sales/expenses summary | 0 |
| `/aide` | Show available commands | 0 |

#### Backend Architecture

```
app/
├── Models/Telegram/
│   ├── TelegramConnection.php
│   ├── TelegramMessage.php
│   └── TelegramBotConfig.php
├── Services/Telegram/
│   ├── TelegramBotService.php         # Core: send/receive, webhook handler
│   ├── TelegramAuthService.php        # Account linking flow
│   ├── CommandHandler.php             # Route commands to handlers
│   └── Commands/
│       ├── StartCommand.php
│       ├── FactureCommand.php
│       ├── DevisCommand.php
│       ├── DepenseCommand.php
│       ├── ScanCommand.php            # Uses AI tokens for OCR
│       ├── ContratCommand.php         # Uses AI tokens for drafting
│       ├── SoldeCommand.php
│       ├── ImpayeesCommand.php
│       ├── ClientCommand.php
│       └── StatsCommand.php
├── Http/Controllers/
│   ├── Backoffice/Telegram/
│   │   └── TelegramSettingsController.php   # Connect/disconnect, configure
│   └── Webhook/
│       └── TelegramWebhookController.php    # Receives Telegram updates
├── Notifications/Channels/
│   └── TelegramChannel.php           # Laravel notification channel
└── Jobs/
    └── ProcessTelegramMessageJob.php
```

#### Webhook Flow

```
Telegram → POST /webhook/telegram/{secret} → TelegramWebhookController
    → Identify tenant from chat_id (via telegram_connections)
    → CommandHandler dispatches to correct Command class
    → Command executes business logic (create invoice, OCR, etc.)
    → Response sent back via TelegramBotService::sendMessage()
```

#### Account Linking Flow

1. User goes to **Settings > Intégrations > Telegram**
2. Platform generates a unique linking code (6 digits, expires in 5 min)
3. User opens Telegram, sends `/start CODE123` to the bot
4. Bot verifies code → links `telegram_user_id` to `user_id` + `tenant_id`
5. User is now connected and can use all commands

#### Notification Integration

Extend existing notification system to push to Telegram:

```php
// In any notification class
public function via($notifiable)
{
    $channels = ['database'];
    if ($notifiable->telegramConnection?->is_active) {
        $channels[] = TelegramChannel::class;
    }
    return $channels;
}
```

**Notification events to push:**
- Invoice paid
- Invoice overdue (reminder)
- New quote received (if customer-facing bot)
- Low stock alert
- Subscription renewal reminder
- Large expense logged
- Daily/weekly business summary (configurable)

#### Configuration (Settings Page)

Each tenant configures:
- Enable/disable Telegram integration
- Choose which notifications to receive
- Set quiet hours (no notifications between 22h–8h)
- View connected users
- Disconnect accounts
- Enable/disable specific commands

#### Packages Needed

```json
{
    "irazasyed/telegram-bot-sdk": "^3.0",
}
```

Or use Telegram Bot API directly via HTTP (lighter, no dependency).

---

## 3. Implementation Priority

### Phase A — AI Tokens Foundation
1. Migrations + models for token system
2. `TokenService` with debit/credit/balance
3. Token purchase UI in settings
4. Plan integration (monthly free tokens)
5. SuperAdmin: manage packages + costs

### Phase B — First AI Actions
1. `AiGatewayService` + Claude provider
2. Invoice extraction from text (paste description → structured invoice)
3. Email compose for sending invoices/quotes
4. OCR extraction from uploaded documents
5. Contract drafting

### Phase C — Telegram Bot
1. Bot setup + webhook endpoint
2. Account linking flow
3. Basic commands (`/solde`, `/impayees`, `/stats`, `/client`)
4. Document creation commands (`/facture`, `/devis`, `/depense`)
5. Notification channel integration

### Phase D — Telegram + AI
1. `/scan` command (photo → OCR → create invoice/expense)
2. `/contrat` command (AI drafting via Telegram)
3. Conversational invoice creation (AI-guided flow in chat)

---

## 4. Revenue Model

| Item | Pricing Example |
|------|----------------|
| Free plan | 0 AI tokens/month, no Telegram |
| Starter plan | 100 tokens/month included |
| Pro plan | 500 tokens/month included |
| Enterprise | 2000 tokens/month included |
| Token pack — Small | 200 tokens = 5€ |
| Token pack — Medium | 500 tokens = 10€ |
| Token pack — Large | 1500 tokens = 25€ |
| Token pack — XL | 5000 tokens = 70€ |
| Auto-recharge | +10% convenience fee |
| Telegram addon | Free with Pro+ plans, 3€/month for Starter |

---

## 5. Technical Requirements

### Environment Variables

```env
# AI
AI_PROVIDER=claude
ANTHROPIC_API_KEY=sk-ant-...
AI_CLAUDE_MODEL=claude-sonnet-4-20250514

# Telegram
TELEGRAM_BOT_TOKEN=123456:ABC-DEF...
TELEGRAM_BOT_USERNAME=facturation_bot
TELEGRAM_WEBHOOK_SECRET=random-secret-string
```

### Queue Configuration

AI actions and Telegram message processing should run on dedicated queues:

```php
// config/queue.php — add dedicated queues
'ai'       => ['driver' => 'redis', 'queue' => 'ai-actions'],
'telegram' => ['driver' => 'redis', 'queue' => 'telegram'],
```

### Rate Limiting

- AI actions: max 30 requests/minute per tenant
- Telegram webhook: max 100 updates/minute per bot
- Token purchases: max 5/hour per tenant

### Security Considerations

- Bot tokens stored encrypted in DB
- Webhook endpoints verified via secret token
- Telegram user identity verified during linking
- AI prompts sanitized to prevent injection
- Token transactions are atomic (DB transactions)
- All AI inputs/outputs logged for audit
- Rate limiting on all AI endpoints
