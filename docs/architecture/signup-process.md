# Signup process

Clarifies `signup process.drawio`.

## Entry points
1. **New building signup form**: the user enters email, password, name, telephone, their unit
   name and the building address (picked from address lookup suggestions).
2. **Join building signup form**: reached through a building's signup URL / QR code
   (`/join/{signup_token}`). The same fields, but the building is already known.

Both create a `User`, a `Contact` (name, address, telephone) and a `Unit`, then send the email
confirmation (Laravel email verification).

## After email confirmation
* **New building route**: if the address matches an existing building (same address provider and
  provider ID), the user joins that building: *User completes Unit detail form*. Otherwise they
  complete the *Building detail form* (building name), and the system creates the building, its
  boilerplate constitution and its signup URL + QR code.
* **Join route**: the building is already known, so the user goes straight on.

## Units
Tenants type their unit name as free text (e.g. "Flat 4"). It is normalised (lower case, extra
whitespace collapsed, "flat"/"apartment"/"room" prefixes kept) and reused if a unit with the
same normalised name already exists in that building. Several tenants can share a unit (HMOs).

## Vouching (tenant authentication)
* Vouching opens once the building has at least `rules.min_signups` (default 3) email-confirmed
  tenants, as set in the building's current constitution.
* Every tenant then has a personal QR code (`/vouch/{vouch_token}`). Another tenant scans it in
  person and confirms, which records a vouch.
* **Bootstrap:** while the building has fewer enrolled members than `rules.vouches_required + 1`,
  any email-confirmed tenant of the building may vouch. After that, only enrolled members of the same
  building may vouch.
* Nobody can vouch for themselves, for a tenant of another building, or for the same person twice.
* A tenant is enrolled (`contacts.enrolled_at`) as soon as they have `rules.vouches_required`
  (default 2) valid vouches.

## Thresholds
The thresholds live in the building's current constitution (`constitutions.rules`), not in app
config. Each building starts with the boilerplate values and can change them by referendum
(milestone 3).
