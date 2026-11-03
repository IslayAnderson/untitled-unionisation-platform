# Architecture
## Tech
* Laravel
* Blades
* Tailwind CSS + Alpine.js (via Vite)
* MySQL/MariaDB (SQLite in-memory for tests)
* Pest for tests
* "node": "22.23.4"
* "php": "^8.3"
* "laravel/framework": "^13.17"
* "laravel/sanctum": "^4.0"
* "laravel/tinker": "^3.0"


## Docs
* /docs/architecture/Entity Diagram.drawio
  * Describes tables and their relationships
  * Amended by /docs/architecture/data-model.md, which takes precedence where they differ
* /docs/architecture/Signup process.drawio
  * Describes how users sign up, buildings are registered and tenants authenticated
  * Rules clarified in /docs/architecture/signup-process.md
* /docs/architecture/data-model.md
  * Changes to the entity diagram agreed before build
* /docs/architecture/signup-process.md
  * Signup, address matching, vouching and enrolment rules
* /docs/architecture/address-lookup.md
  * Swappable address lookup harness (Geoapify by default)
* /docs/architecture/contacts.md
  * Landlords, external contacts and mailing lists (milestone 4)
* /docs/architecture/mail.md
  * Instant mail and bulletin mail: how union email is approved and sent

## Milestones
1. Scaffold the app, migrate every entity in the entity diagram, build the signup flow end to end
   (signup → confirm email → building/unit → vouching → enrolment)
2. Bulletins, threads/replies, polls
3. Policies, constitutions and referendums (amending the constitution by vote)
4. Landlords, contacts and mailing lists
