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
* [Data model](data-model.md): the decisions behind the schema, agreed before build, with the [entity diagram](<Entity Diagram.svg>) of every table, column and relationship as they are now
* [Signup process](signup-process.md): signup, address matching, vouching and enrolment rules, with the [signup diagram](<signup process.svg>) of how tenants sign up, buildings are registered and tenants are vouched for
* [Address lookup](address-lookup.md): the swappable address lookup harness (Geoapify by default)
* [Governance](governance.md): constitution amendments, policies, roles and referendums (milestone 3), with the [referendum diagram](<referendum process.svg>) and the [leaving and removal diagram](<leaving and removal process.svg>)
* [Polls](polls.md): secret-ballot polls on bulletins, with the [polls diagram](<polls process.svg>)
* [Contacts, landlords and mailing lists](contacts.md): landlords, external contacts and mailing lists (milestone 4)
* [Union mail](mail.md): instant mail and bulletin mail, how union email is approved and sent, and email replies, with the [union mail diagram](<union mail process.svg>) and the [email replies diagram](<email replies process.svg>)
* [Image uploads](uploads.md): compression, metadata stripping and Cloudflare CSAM scanning
* [Platform admin](admin.md): usage numbers per union, clean-up tools and union exports, with the [clean-up diagram](<clean-up process.svg>)
* [Demo union](demo.md): the public read-only demo union, Larkspur House
* [Front end](frontend.md): the GOV.UK Design System, how pages are built, and the app's own components

Each diagram is a [draw.io](https://www.drawio.com) file with an SVG copy to view in a browser or on GitHub. The process diagrams use the same shapes: rounded boxes are steps, diamonds are decisions, and Object boxes are the records a step creates. All the diagrams are drawn by the scripts in [diagrams](diagrams): change them there, not in draw.io (see the [knowledge base](../knowledge-base/README.md#redrawing-the-diagrams)).

See also the [knowledge base](../knowledge-base/README.md) for the platform's commands, and [tests.md](../../tests.md) for what the tests check.

## Milestones
1. Scaffold the app, migrate every entity in the entity diagram, build the signup flow end to end
   (signup → confirm email → building/unit → vouching → enrolment)
2. Bulletins, threads/replies, polls
3. Policies, constitutions and referendums (amending the constitution by vote)
4. Landlords, contacts and mailing lists
