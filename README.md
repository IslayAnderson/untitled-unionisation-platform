# untitled-unionisation-platform

Untitled unionisation platform is a unified platform aimed at apartment blocks and HMOs to easily unionise, make demands to the building owner and hold their landlords/estate-agents to account.

# Docs

* [Architecture](docs/architecture/root.md): the tech stack, milestones, and an index of the docs below
  * [Data model](docs/architecture/data-model.md) and the [entity diagram](<docs/architecture/Entity Diagram.svg>) ([draw.io](<docs/architecture/Entity Diagram.drawio>))
  * [Signup process](docs/architecture/signup-process.md): signup, address matching, vouching and enrolment, with the [signup diagram](<docs/architecture/signup process.svg>) ([draw.io](<docs/architecture/signup process.drawio>))
  * [Address lookup](docs/architecture/address-lookup.md)
  * [Governance](docs/architecture/governance.md): constitution, policies, roles and referendums, with the [referendum diagram](<docs/architecture/referendum process.svg>) ([draw.io](<docs/architecture/referendum process.drawio>)) and the [leaving and removal diagram](<docs/architecture/leaving and removal process.svg>) ([draw.io](<docs/architecture/leaving and removal process.drawio>))
  * [Polls](docs/architecture/polls.md), with the [polls diagram](<docs/architecture/polls process.svg>) ([draw.io](<docs/architecture/polls process.drawio>))
  * [Contacts, landlords and mailing lists](docs/architecture/contacts.md)
  * [Union mail](docs/architecture/mail.md): approval, sending and email replies, with the [union mail diagram](<docs/architecture/union mail process.svg>) ([draw.io](<docs/architecture/union mail process.drawio>)) and the [email replies diagram](<docs/architecture/email replies process.svg>) ([draw.io](<docs/architecture/email replies process.drawio>))
  * [Image uploads](docs/architecture/uploads.md)
  * [Platform admin](docs/architecture/admin.md): usage numbers, clean-up and union exports, with the [clean-up diagram](<docs/architecture/clean-up process.svg>) ([draw.io](<docs/architecture/clean-up process.drawio>))
  * [Demo union](docs/architecture/demo.md)
  * [Front end](docs/architecture/frontend.md): the GOV.UK Design System
* [Knowledge base](docs/knowledge-base/README.md): how to use the platform's Artisan commands, the scheduler, queue workers and Tinker
* [Tests](tests.md): what each test file checks, with the results of the latest run

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

Run the tests with `php artisan test` (Pest, against in-memory SQLite). [tests.md](tests.md) summarises what
each test file checks, with the results of the latest run.
