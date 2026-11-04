# Architecture
## Tech
* Laravel
* Blades
* GOV.UK Frontend (Sass) + Alpine.js (via Vite)
* MySQL/MariaDB (SQLite in-memory for tests)
* Pest for tests
* "node": "22.23.4"
* "php": "^8.3"
  * `config.platform.php` in composer.json is 8.3.0, so the lock file installs on PHP 8.3 servers even when
    it is updated on a newer PHP. Raise it only when every server runs the newer version.
* "laravel/framework": "^13.17"
* "laravel/sanctum": "^4.0"
* "laravel/tinker": "^3.0"


## Docs
* [Data model](data-model.md): changes to the entity diagram agreed before build, with the [entity diagram](<Entity Diagram.drawio>) of tables and their relationships. Where they differ, the data model takes precedence
* [Signup process](signup-process.md): signup, address matching, vouching and enrolment rules, clarifying the [signup diagram](<signup process.drawio>) of how users sign up, buildings are registered and tenants authenticated
* [Address lookup](address-lookup.md): the swappable address lookup harness (Geoapify by default)
* [Governance](governance.md): constitution amendments, policies, roles and referendums (milestone 3)
* [Polls](polls.md): secret-ballot polls on bulletins
* [Contacts, landlords and mailing lists](contacts.md): landlords, external contacts and mailing lists (milestone 4)
* [Union mail](mail.md): instant mail and bulletin mail, how union email is approved and sent, and email replies
* [Image uploads](uploads.md): compression, metadata stripping and Cloudflare CSAM scanning
* [Platform admin](admin.md): usage numbers per union, clean-up tools and union exports
* [Demo union](demo.md): the public read-only demo union, Larkspur House
* [Front end](frontend.md): the GOV.UK Design System, how pages are built, and the app's own components

See also the [knowledge base](../knowledge-base/README.md) for the platform's commands, and [tests.md](../../tests.md) for what the tests check.

## Milestones
1. Scaffold the app, migrate every entity in the entity diagram, build the signup flow end to end
   (signup → confirm email → building/unit → vouching → enrolment)
2. Bulletins, threads/replies, polls
3. Policies, constitutions and referendums (amending the constitution by vote)
4. Landlords, contacts and mailing lists
