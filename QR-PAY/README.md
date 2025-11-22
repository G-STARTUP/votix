# Moneroo Payment Gateway Integration

Moneroo has been integrated as a crypto (native) add-money gateway.

Environment Variables (.env):

```dotenv
MONEROO_API_KEY=your_key_here
MONEROO_ENV=SANDBOX   # or PRODUCTION
MONEROO_WEBHOOK_SECRET=your_webhook_secret
MONEROO_TIMEOUT=30
```

Config: `config/moneroo.php` controls API key, mode and timeout.

Service Provider: `App\Providers\MonerooServiceProvider` registers a singleton `moneroo.client`.

Trait: `App\Traits\PaymentGateway\Moneroo` handles transaction initialization similar to other crypto gateways.

Routes: Webhook endpoint registered at `POST /api/add-money/moneroo/webhook` (named route `api.moneroo.webhook`).

Constants: Added `PaymentGatewayConst::MONEROO` and mapped to `monerooInit`.

Workflow (Add Money):

1. Admin enables Moneroo gateway and attaches crypto asset addresses.
2. User initiates Add Money selecting Moneroo; trait returns address + required fields.
3. User submits txn hash; transaction enters WAITING state.
4. Webhook (if configured) or manual confirmation updates status.

Webhook Signing: Uses `X-Moneroo-Signature` header with HMAC SHA256 over raw payload and `MONEROO_WEBHOOK_SECRET`.

Notes:

- If Moneroo SDK class differs from `Moneroo\\Client`, adjust provider accordingly.
- Fallback stub is returned if SDK class not found.
- Extend trait for withdrawal or additional actions as needed.

<<<<<<<< Update Guide >>>>>>>>>>>

Immediate Older Version: 1.5.0
Current Version: 1.6.0

Feature Update:

1. Added bulk status management for currencies.
2. Admin can add new user, agent, merchant based on project registration data.
3. Dynamic sections can now be managed directly from the admin panel.
4. The Extensions section has been updated to verify whether credentials are filled in.
5. Added a dedicated All Notifications page in the admin panel.
6. Admins can create support tickets on behalf of user, agent, and merchant.
7. Error logs are now viewable in the admin panel, with an option to clear them.
8. Added support for dynamic admin URL access.
9. Improved the roles and permissions management system.
10. Integrated Authorize.net as a new payment gateway.


Please Use This Commands On Your Terminal To Update Full System

1. To Run project Please Run This Command On Your Terminal
    php artisan file:rename "app/Http/Helpers/Api/helpers.php" "app/Http/Helpers/Api/Helpers.php" && composer update && php artisan migrate

2. To Update Web & App Version Please Run This Command On Your Terminal
    php artisan db:seed --class=Database\\Seeders\\Update\\VersionUpdateSeeder
    php artisan o:c
