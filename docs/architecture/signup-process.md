# Signup process

Clarifies `signup process.drawio`.

## Entry points
1. **New building signup form**: the user enters email, password, name, telephone, their unit
   name and the building address (picked from address lookup suggestions).
2. **Join building signup form**: reached through a building's signup URL / QR code
   (`/join/{signup_token}`). The same fields, but the building is already known.

Both create a `User` and a `Contact` (name, address, telephone), then send the email confirmation
(Laravel email verification). The building/address and unit name are held in
`contacts.pending_signup` until the email is confirmed; only then is the `Unit` created and the
tenant placed, so unconfirmed accounts can't create buildings or count towards thresholds.

## After email confirmation
* **New building route**: if the address matches an existing building (same address provider and
  provider ID), the user joins that building: *User completes Unit detail form*. Otherwise they
  complete the *Building detail form* (building name), and the system creates the building, its
  boilerplate constitution and its signup URL + QR code.
* **Join route**: the building is already known, so the user goes straight on.

## Units
Tenants type their unit name as free text (e.g. "Flat 4"). It is normalised (lower case,
punctuation treated as spaces, whitespace collapsed, so "FLAT 4." matches "Flat 4") and reused if a unit with the
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

## Vouching bulletin
Every union is created with a pinned bulletin, "Arrange vouching" (`App\Support\VouchingBulletin`,
`bulletins.system_key = vouching`), for members and new neighbours to arrange when and where to meet.
Unions that existed before it was added got one in the migration.

* It's the **only** bulletin email-confirmed tenants who aren't members yet can see (the
  `view-bulletin` gate). All other bulletins, mail, contacts and mailing lists stay members-only, and
  return 404 to them.
* Tenants who aren't members yet can reply in **plain text only** (up to 2,000 characters). They get
  a plain text box rather than the editor, and whatever they send is escaped, so no formatting,
  links or images. Members reply as usual, with rich text and images.
* Replies are labelled "Member" or "Not yet vouched for", since anyone who has signed up can read the
  thread, including people who may not live in the building. The bulletin tells everyone not to
  post flat numbers or phone numbers, and to meet somewhere shared.
* The dashboard links to it for members and for tenants waiting to be vouched for.

## Thresholds
The thresholds live in the building's current constitution (`constitutions.rules`), not in app
config. Each building starts with the boilerplate values and can change them by referendum
(see `governance.md`).
