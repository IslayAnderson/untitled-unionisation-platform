# Data model amendments

These changes to `Entity Diagram.drawio` were agreed before the build. Where this
document and the diagram disagree, this document wins.

## Naming
Table names follow Laravel conventions (snake_case plurals). Spelling in the diagram is corrected:

| Diagram | Table |
|---|---|
| Buildings/Unions | `buildings` (a building *is* its union, 1:1) |
| Bullitens | `bulletins` |
| Contact/Mailinglist Type | `contact_types` (used by both contacts and mailing lists) |
| Landlord Type | `landlord_types` |
| Poll Answers | `poll_answers` |
| Poll Submissions | `poll_submissions` |
| Referendum Submissions | `referendum_submissions` |
| Role Actions | `role_actions` |

Every table gets an auto-increment `id` primary key and `created_at` / `updated_at`.

## Relationship changes
* **Contacts ↔ Mailing lists is many-to-many.** The `mailing_list` FK on Contacts is replaced by a
  `contact_mailing_list` pivot table.
* **Contacts ↔ Roles is many-to-many.** The `role` FK on Contacts is replaced by a `contact_role`
  pivot table.
* **Policies → Buildings is one-to-many.** A building can have many policies (the one-to-one
  arrow in the diagram was a mistake).
* **Referendums are polymorphic.** The `policy` FK is replaced by `referendable_type` /
  `referendable_id`, which point to either a Policy or a Constitution. A referendum proposes that
  version; if it passes, that version is published and becomes the building's current one.
* **Referendum submission answers** are an enum: `for`, `against`, `abstain`.

## Added columns
* `buildings`
  * Structured address: `address_line_1`, `address_line_2`, `city`, `postcode`
  * `address_provider` and `address_provider_id`: the lookup provider's identifier for the
    building, which is what we match on (see `address-lookup.md`). Unique together.
  * `latitude`, `longitude`
  * `signup_token`: random token behind the building's join URL / QR code
  * `building_owner_id` (Landlord) is nullable. Landlords are added after setup, not during signup.
  * `contact_email`: the union's reply-to address for emails sent to mailing lists
* `landlords`
  * `building_id`: the union that keeps this record. Landlord records are private to each union
    (see `contacts.md`).
* `constitutions`
  * `rules` (JSON): machine-readable settings the code reads, alongside the human-readable rich text.
    Defaults from the boilerplate:
    * `min_signups`: 3, the tenants needed before vouching starts
    * `vouches_required`: 2, the vouches needed to enrol
    * `referendum_quorum_percent`: 50
    * `referendum_pass_percent`: 50 (strictly more than this share of for+against votes)
    * `instant_mail_approval_percent`: 10, `instant_mail_approver_percent`: 20,
      `instant_mail_expiry_hours`: 48
    * `bulletin_mail_approval_percent`: 50, `bulletin_mail_expiry_hours`: 168
  * Each building starts with the boilerplate constitution and can amend it by referendum.
* `contacts`
  * `building_id`: the union the contact belongs to (Contacts already link to a Unit; this is set for
    tenants so they can be queried by building)
  * `enrolled_at`: when the tenant became a union member
  * `vouch_token`: random token behind the tenant's personal QR code
  * `pending_signup` (JSON): the building/address and unit chosen at signup, held until the email is
    confirmed and the tenant is placed in a building, then cleared
* `units`
  * `normalised_name`: used to dedupe free-text unit names per building (unique with `building_id`)

## Added tables
* `vouches`: `voucher_contact_id`, `vouchee_contact_id`, timestamps. Unique on the pair. Records one
  tenant scanning another's QR code.
* `contact_mailing_list`, `contact_role`: pivots (see above).
* `union_mails`: an email from the union to a mailing list, and its approval state (see `mail.md`).
  `kind` (instant | bulletin), `status` (pending | sent | rejected | expired | withdrawn),
  `building_id`, `mailing_list_id`, `author_contact_id`, `bulletin_id` (the source bulletin, or the
  bulletin created when an instant mail is sent), `subject`, `body`, `approvals_required`,
  `expires_at`, `decided_at`, `recipient_count`.
* `union_mail_approvers`: who may approve a mail and their `decision` (approve | reject).

## Lookup data
`landlord_types` (Individual, Company, Housing association, Local authority) and `contact_types`
(Tenant, Landlord, Letting agent, Building management) are seeded by the migration.

## Users and contacts
A User is a login (`email`, `password`, `email_verified_at`) that belongs to one Contact. The
default Laravel `users.name` column is dropped; the name lives on the Contact. Contacts
also represent people without logins, such as landlord or agent staff.
