# Contacts, landlords and mailing lists

Milestone 4. Lets a union keep an address book of the people it deals with and email them.

## Who can do what
* Any enrolled member of the union can view, add, edit and delete its landlords, contacts and
  mailing lists, and send emails to its mailing lists. (Role-based permissions can tighten this
  later.)
* Tenants who aren't members yet see none of this.
* Every record belongs to one union. Members only ever see their own union's records.

## Landlords
"Landlords" covers anyone the union deals with as an organisation or person: the building owner,
letting agents, managing agents, freeholders. Each has a type (`landlord_types`), name, address,
telephone, email and company number.

* Landlords belong to the union that added them (`landlords.building_id`). Two unions in
  buildings owned by the same company each keep their own record, so one union's notes are never
  visible to another.
* One landlord can be marked as the **building owner** (`buildings.building_owner_id`).
* One of a landlord's contacts can be its **primary contact** (`landlords.primary_contact_id`).

## Contacts
External contacts are people at a landlord/agent (or standalone, e.g. a council officer). They are
`contacts` rows with `building_id` set to the union and no User. Tenant contacts (those with a
login) are never shown, edited or added to mailing lists here.

## Mailing lists
A named group of the union's external contacts, optionally tied to a contact type and/or a
landlord (e.g. "Managing agent – repairs").

### Sending
* A member writes a subject and plain-text message to a list. The platform emails each contact on
  the list that has an email address, **separately** (recipients don't see each other).
* Emails come from the platform's address, with the union's name as the sender name, and are
  signed by the union. **The sending member is never named**, to protect tenants from retaliation.
* Replies go to the union's reply-to address (`buildings.contact_email`, set by members on the
  mailing lists page), or to the platform address if none is set.
* Each send is logged (`mailing_list_messages`: subject, body, recipient count, sent time, and the
  sending member for internal accountability). Members can see the log; the sender is shown to
  members only.
* Emails are queued, so a queue worker (`php artisan queue:work`) must be running to deliver them.
