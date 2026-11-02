# untitled-unionisation-platform

Untitled unionisation platform is a unified platform aimed at apartment blocks and HMOs to easily unionise, make demands to the building owner and hold their landlords/estate-agents to account.

# Docs

* Architecture
  * /docs/architecture/root.md
* Knowledge Base
  * /docs/knowledge-base/root.md

# Development

Requires PHP 8.3+, Composer, Node 22 and MySQL/MariaDB.

```bash
composer install
npm install
cp .env.example .env   # then set DB_* and, for real addresses, GEOAPIFY_API_KEY
php artisan key:generate
php artisan migrate
npm run build
php artisan serve
```

With `ADDRESS_LOOKUP_DRIVER=fake` (the default in `.env.example`) the address search returns a few
sample addresses (try "example"), and no API key is needed. With `MAIL_MAILER=log`, confirmation
emails are written to `storage/logs/laravel.log`.

Run the tests with `php artisan test` (Pest, against in-memory SQLite).
