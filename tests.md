# Tests

What the automated tests check, and the result of the latest run. Run them yourself with:

```bash
php artisan test
```

They use Pest against an in-memory SQLite database, and fake mail, notifications and outside services, so they need no network access or `.env` secrets.

## Latest results

**275 of 275 passed** (1380 assertions) on 4 November 2026, with PHP 8.4.12 and Pest 4.7.8, in 29 seconds.

| Area | File | Tests | Result |
| --- | --- | ---: | --- |
| [Rich text](#rich-text) | `tests/Unit/RichTextTest.php` | 11 | Passed |
| [Units](#units) | `tests/Unit/UnitNameTest.php` | 5 | Passed |
| [Address lookup](#address-lookup) | `tests/Feature/AddressLookupTest.php` | 5 | Passed |
| [Admin and clean-up](#admin-and-clean-up) | `tests/Feature/AdminTest.php` | 14 | Passed |
| [Bulletin replies](#bulletin-replies) | `tests/Feature/BulletinRepliesTest.php` | 14 | Passed |
| [Landlords and contacts](#landlords-and-contacts) | `tests/Feature/ContactsTest.php` | 15 | Passed |
| [Demo union](#demo-union) | `tests/Feature/DemoTest.php` | 9 | Passed |
| [Editing posts](#editing-posts) | `tests/Feature/EditingPostsTest.php` | 7 | Passed |
| [Email template](#email-template) | `tests/Feature/EmailTemplateTest.php` | 5 | Passed |
| [Image uploads](#image-uploads) | `tests/Feature/ImageUploadTest.php` | 9 | Passed |
| [Flyers and posters](#flyers-and-posters) | `tests/Feature/InviteMaterialsTest.php` | 7 | Passed |
| [Mail attachments](#mail-attachments) | `tests/Feature/MailAttachmentsTest.php` | 10 | Passed |
| [Mailing lists](#mailing-lists) | `tests/Feature/MailingListTest.php` | 9 | Passed |
| [Membership](#membership) | `tests/Feature/MembershipTest.php` | 9 | Passed |
| [Onboarding](#onboarding) | `tests/Feature/OnboardingTest.php` | 6 | Passed |
| [Outside resources](#outside-resources) | `tests/Feature/OutsideResourcesTest.php` | 26 | Passed |
| [Polls](#polls) | `tests/Feature/PollTest.php` | 17 | Passed |
| [Public pages](#public-pages) | `tests/Feature/PublicPagesTest.php` | 7 | Passed |
| [QR scans](#qr-scans) | `tests/Feature/QrScanTest.php` | 4 | Passed |
| [Referendums](#referendums) | `tests/Feature/ReferendumTest.php` | 17 | Passed |
| [Registration](#registration) | `tests/Feature/RegistrationTest.php` | 7 | Passed |
| [Roles](#roles) | `tests/Feature/RoleTest.php` | 10 | Passed |
| [Discussions](#discussions) | `tests/Feature/ThreadTest.php` | 3 | Passed |
| [Union mail](#union-mail) | `tests/Feature/UnionMailTest.php` | 26 | Passed |
| [Error messages](#error-messages) | `tests/Feature/ValidationMessagesTest.php` | 4 | Passed |
| [Vouching bulletin](#vouching-bulletin) | `tests/Feature/VouchingBulletinTest.php` | 8 | Passed |
| [Vouching](#vouching) | `tests/Feature/VouchingTest.php` | 11 | Passed |

A test with examples (a Pest dataset) counts once for each example.

## What each file tests

### Rich text

[`tests/Unit/RichTextTest.php`](tests/Unit/RichTextTest.php). Converting between posted HTML and plain text, blank detection, and stripping quoted history from email replies.

- ✅ Turns rich text into readable plain text
- ✅ Turns plain text into escaped paragraphs
- ✅ Knows when rich text is blank (3 examples: empty paragraph, line breaks, text)
- ✅ Strips quoted history from email replies (6 examples: gmail, gmail wrapped, outlook, outlook headers, quoted lines, no quote)

### Units

[`tests/Unit/UnitNameTest.php`](tests/Unit/UnitNameTest.php). Normalising flat names (“Flat 3”, “3”) so a building doesn’t get duplicates.

- ✅ Normalises unit names for deduping (5 examples: “Flat 4”, “  FLAT   4 ”, “Flat 4.” all become “flat 4”; “Room 2B” becomes “room 2b”; “Flat 4-A” becomes “flat 4 a”)

### Address lookup

[`tests/Feature/AddressLookupTest.php`](tests/Feature/AddressLookupTest.php). The address search drivers (fake and Geoapify) and the JSON search endpoint.

- ✅ Resolves the configured driver
- ✅ Searches addresses as JSON
- ✅ Maps Geoapify autocomplete results
- ✅ Finds a Geoapify place by ID, keeping the searched ID
- ✅ Returns null for an unknown Geoapify place

### Admin and clean-up

[`tests/Feature/AdminTest.php`](tests/Feature/AdminTest.php). Admin accounts, usage numbers without names or content, inactive unions, the clean-up warnings and deletions, and union exports.

- ✅ Grants and revokes admin access from the command line
- ✅ Creates an admin account that isn't a tenant, asking for the password
- ✅ Refuses to create an admin with an email already in use or a mismatched password
- ✅ Hides the admin area from everyone else
- ✅ Shows usage numbers for each union without names or content
- ✅ Counts unions with nothing new in 30 days as inactive
- ✅ Emails account holders first, and deletes only those who do not log in within 7 days
- ✅ Does not delete an account used without logging in again
- ✅ Emails abandoned unions and deletes those nobody came back to
- ✅ Splits a long section into PDFs of no more than the page limit
- ✅ Deletes exports once their links have expired
- ✅ Lets a member logging in stop a union being deleted
- ✅ Lets the admin call off a union deletion
- ✅ Deletes a union and everything in it, after emailing its members and waiting a week

### Bulletin replies

[`tests/Feature/BulletinRepliesTest.php`](tests/Feature/BulletinRepliesTest.php). Reply addresses on union mail, landlord email replies added to bulletins and forwarded, hostile replies shown only as escaped text, and members’ replies.

- ✅ Sends union mail with a reply address for that mail
- ✅ Gives pending mail from before reply addresses a token when it is sent
- ✅ Sends replies to the union inbox when inbound mail is not set up
- ✅ Adds an email reply to the bulletin without the quoted message, and forwards a copy
- ✅ Shows a hostile email reply only as escaped text (3 examples: script in the text part, HTML-only email, escaped markup that decodes to a script)
- ✅ Emails the copy to every member separately when the union has no inbox
- ✅ Shows email replies and members' posts in the bulletin thread
- ✅ Ignores emails that are not replies to sent union mail, and duplicates
- ✅ Does nothing when inbound mail is not set up
- ✅ Lets members post cleaned replies in a bulletin thread
- ✅ Rejects blank replies and hides other unions' bulletins
- ✅ Cleans bulletin HTML when it is posted

### Landlords and contacts

[`tests/Feature/ContactsTest.php`](tests/Feature/ContactsTest.php). Managing landlords and their contacts, only within the member’s own union, never exposing tenants.

- ✅ Sends tenants who are not yet members back to the dashboard
- ✅ Sends guests to log in
- ✅ Lists only this union's landlords and contacts, never tenants
- ✅ Adds a landlord and marks it as the building owner
- ✅ Unmarks the building owner
- ✅ Adds a contact as a landlord's primary contact
- ✅ Clears the primary contact when they move to another landlord
- ✅ Requires a contact name
- ✅ Rejects linking a contact to another union's landlord
- ✅ Keeps a deleted landlord's contacts
- ✅ Hides other unions' records and tenant contacts from editing (3 examples: another union's landlord, another union's contact, a tenant of this union)
- ✅ Only lets enrolled members of the same union manage a record
- ✅ Shows members the contacts and mailing list links

### Demo union

[`tests/Feature/DemoTest.php`](tests/Feature/DemoTest.php). Building the demo union, browsing it read-only, the example printables and emails, and turning it off.

- ✅ Builds a lived-in union with no real email addresses
- ✅ Builds when the database returns ids as text, as some MySQL drivers do
- ✅ Replaces the previous demo when rebuilt, and can remove it
- ✅ Lets anyone look around as the tenant of C503
- ✅ Shows anyone the demo union's printables, with codes that lead to the demo
- ✅ Saves nothing a demo visitor does
- ✅ Shows anyone every email the platform sends, as the demo union's, without sending them
- ✅ Hides the demo when it is turned off, and nobody can join it
- ✅ Leaves the demo out of clean-up

### Editing posts

[`tests/Feature/EditingPostsTest.php`](tests/Feature/EditingPostsTest.php). When authors can edit or delete bulletins, discussions and replies, and when they can’t.

- ✅ Lets authors edit and delete bulletins and discussions with cleaned text (2 examples: bulletins; threads)
- ✅ Stops changes once a bulletin or discussion has replies, and by anyone but the author (2 examples: bulletins; threads)
- ✅ Stops changes to bulletins with polls or mail, the vouching bulletin and referendum discussions
- ✅ Lets members change their own reply until someone replies after it
- ✅ Keeps tenants waiting to be vouched for to plain text, and other unions out

### Email template

[`tests/Feature/EmailTemplateTest.php`](tests/Feature/EmailTemplateTest.php). Every email uses the platform template, union mail keeps its formatting, forwarded replies are escaped, Markdown can’t load outside images, and the sample-email command.

- ✅ Renders notifications in the platform's template
- ✅ Keeps Markdown in someone's text from loading images from another site
- ✅ Heads and signs union mail with the union, keeping its rich text as HTML
- ✅ Shows forwarded replies as escaped text
- ✅ Sends a sample of every email to one address

### Image uploads

[`tests/Feature/ImageUploadTest.php`](tests/Feature/ImageUploadTest.php). Resizing, rotation, metadata stripping, allowed types, who can upload, and Cloudflare cache warming.

- ✅ Shrinks large photos to 2000px and stores them as JPEG
- ✅ Applies the phone's rotation and strips metadata
- ✅ Keeps transparent images as PNG and converts opaque ones to JPEG
- ✅ Rejects files that are not images (3 examples: svg, text renamed, too big)
- ✅ Only lets members upload
- ✅ Warms Cloudflare's cache after upload when enabled
- ✅ Keeps only uploaded images in rich text

### Flyers and posters

[`tests/Feature/InviteMaterialsTest.php`](tests/Feature/InviteMaterialsTest.php). Printable join materials for tenants and campaign leaflets for admins, without members’ names.

- ✅ Shows tenants printable flyers and posters with their building's join link
- ✅ Offers a version that invites neighbours to look around the demo first
- ✅ Explains how the union works on the leaflet, using the constitution's numbers
- ✅ Links to the print page from the dashboard
- ✅ Hides the print page from users without a building
- ✅ Gives admins generic leaflets for campaigns, linking to the home page
- ✅ Keeps the campaign leaflets to admins

### Mail attachments

[`tests/Feature/MailAttachmentsTest.php`](tests/Feature/MailAttachmentsTest.php). Storing, sending and downloading attachments on union mail, and refusing unsafe files.

- ✅ Stores documents privately as they are, and images compressed
- ✅ Sends the attachments with the mail once it is approved
- ✅ Adds attachments to bulletin mail
- ✅ Refuses attachments it shouldn't send (5 examples: a program, a web page, too many, one too big, too big together)
- ✅ Keeps no files when the mail can't be created
- ✅ Lets members of the union, and nobody else, download attachments

### Mailing lists

[`tests/Feature/MailingListTest.php`](tests/Feature/MailingListTest.php). Lists of landlord contacts, cleaned message bodies, and never naming the sending member.

- ✅ Creates a mailing list of the union's contacts
- ✅ Rejects tenants and other unions' contacts on a list (2 examples: a tenant, another union's contact)
- ✅ Never names the sending member in the email
- ✅ Strips unsafe HTML from the message body but keeps formatting
- ✅ Hides other unions' mailing lists
- ✅ Saves the union's reply-to address
- ✅ Does not HTML-escape the plain-text version
- ✅ Says where copies of replies go

### Membership

[`tests/Feature/MembershipTest.php`](tests/Feature/MembershipTest.php). Leaving, removal by referendum, account deletion, and emailing leavers the union’s records.

- ✅ Lets a member leave, ending their roles, nominations and approvals, and emails them the records
- ✅ Rejects pending mail that can no longer get enough approvals when an approver leaves
- ✅ Removes a member by referendum, with a reason, and they can't be vouched for again
- ✅ Uses the constitution's removal threshold, and keeps the member if it fails
- ✅ Refuses removals it shouldn't hold
- ✅ Still bars a member who left while the vote on removing them was open, without a second export
- ✅ Deletes an account after checking the password, emailing a member the records
- ✅ Lets a tenant waiting to be vouched for delete their account, without an export
- ✅ Emails the leaver the union's records

### Onboarding

[`tests/Feature/OnboardingTest.php`](tests/Feature/OnboardingTest.php). Email confirmation, creating a building with its starter constitution, and placing tenants in units.

- ✅ Confirms the email and moves on to onboarding
- ✅ Shows the building detail form when the address is new
- ✅ Creates the building, boilerplate constitution and unit
- ✅ Sends a tenant whose address is already registered to the unit form
- ✅ Dedupes unit names within a building
- ✅ Sends placed tenants straight to the dashboard

### Outside resources

[`tests/Feature/OutsideResourcesTest.php`](tests/Feature/OutsideResourcesTest.php). Posted HTML can’t make a member’s browser load anything from another site: images, srcset, styles, noscript, frames, media, SVG, meta refresh, base, forms.

- ✅ Drops anything in posted HTML that would load from another site (25 examples: image from another site, protocol-relative image, srcset on an uploaded image, our host as a user name, lookalike host, uploads URL climbing out, data URI image, noscript image, style element, style import, style attribute, stylesheet link, iframe, object and embed, video and audio, svg image, table background, image input, link ping, meta refresh, base, form, template, body background, unquoted broken image)
- ✅ Keeps images uploaded to the platform

### Polls

[`tests/Feature/PollTest.php`](tests/Feature/PollTest.php). Adding polls to bulletins, secret one-off voting, and hiding results until a poll closes.

- ✅ Lets a bulletin's author add a poll, ignoring blank answers
- ✅ Rejects polls with too few or repeated answers, or a closing date in the past (4 examples: one answer, repeated answers, closing in the past, open for too long)
- ✅ Only lets the author add polls, and never on the vouching bulletin
- ✅ Records that a member voted separately from what they chose, without timestamps
- ✅ Counts every answer ticked in a multiple-choice poll as one ballot
- ✅ Refuses votes it shouldn't count (4 examples: two answers in a single-choice poll, another poll's answer, after the deadline, after it was closed early)
- ✅ Doesn't let a member vote twice or change their vote
- ✅ Only lets members of the poll's union vote
- ✅ Hides the results from everyone, including the author, until the poll closes
- ✅ Lets only the author close a poll early
- ✅ Lists open polls a member hasn't voted in on the dashboard

### Public pages

[`tests/Feature/PublicPagesTest.php`](tests/Feature/PublicPagesTest.php). How vouching works, privacy policy, the cookie message, feedback, and how the platform is run.

- ✅ Explains vouching with the default numbers
- ✅ Explains vouching with a tenant's own union's numbers
- ✅ Shows the privacy policy with the controller's contact details
- ✅ Shows a cookie message until it is hidden
- ✅ Emails feedback to the privacy contact
- ✅ Accepts anonymous feedback, and shows errors on the feedback page
- ✅ Explains how the platform is run with made-up examples, never real unions

### QR scans

[`tests/Feature/QrScanTest.php`](tests/Feature/QrScanTest.php). Counting QR code scans once per session, without recording who scanned.

- ✅ Marks the URL a QR code opens as scanned
- ✅ Counts a join code being scanned once per session, without recording who scanned it
- ✅ Counts vouching, campaign and demo codes
- ✅ Shows admins how many times QR codes were scanned

### Referendums

[`tests/Feature/ReferendumTest.php`](tests/Feature/ReferendumTest.php). Constitutional amendments and policy changes by secret referendum, with quorum and thresholds.

- ✅ Puts a constitutional amendment to a referendum with a discussion, and tells the other members
- ✅ Rejects amendments with numbers out of range, and a second amendment while one is open
- ✅ Adopts the amendment when enough members vote for it, and the platform follows the new numbers
- ✅ Decides by quorum and the pass threshold, with abstentions counting only towards quorum (4 examples: one vote of four misses the 50% quorum, abstentions make the quorum, one for passes, a tie fails, all abstain fails)
- ✅ Records that a member voted separately from how, and only once
- ✅ Keeps other unions, tenants and late votes out
- ✅ Lets only the proposer withdraw, and nothing changes
- ✅ Replaces a policy with its revision when the revision passes
- ✅ Repeals a policy
- ✅ Only revises or repeals policies in force in the member's union
- ✅ Shows the result and what changed once a referendum closes
- ✅ Lets tenants waiting to be vouched for read the constitution, but not other unions
- ✅ Lists referendums waiting for a member's vote on the dashboard
- ✅ Fills the constitution's numbers into its text, so amending a number changes the text

### Registration

[`tests/Feature/RegistrationTest.php`](tests/Feature/RegistrationTest.php). Signing up by address or join link, logging in and out.

- ✅ Registers a tenant for a new building and sends a confirmation email
- ✅ Rejects an address ID the lookup provider does not know
- ✅ Registers a tenant through a building join link
- ✅ Returns 404 for an unknown join link
- ✅ Keeps unconfirmed tenants out of onboarding
- ✅ Logs a tenant in and out
- ✅ Does not double-escape names in the page title

### Roles

[`tests/Feature/RoleTest.php`](tests/Feature/RoleTest.php). Creating roles and electing, removing and standing down members by referendum.

- ✅ Creates a role with its responsibilities when the referendum passes
- ✅ Elects a member to a role, and removes them, by referendum
- ✅ Refuses elections it shouldn't hold (3 examples: a tenant who isn't a member, someone already holding it, someone already nominated)
- ✅ Refuses changes to roles that aren't in force or belong to another union
- ✅ Lets a nominee decline by withdrawing their election
- ✅ Abolishes a role, and everyone holding it stops
- ✅ Lets a member step down from a role without a vote
- ✅ Doesn't elect a nominee who left the union while the vote was open

### Discussions

[`tests/Feature/ThreadTest.php`](tests/Feature/ThreadTest.php). Starting, listing and replying to discussions within a union.

- ✅ Lets a member start a discussion with cleaned rich text
- ✅ Lists discussions with the most recent activity first
- ✅ Lets members reply, and keeps other unions and tenants out

### Union mail

[`tests/Feature/UnionMailTest.php`](tests/Feature/UnionMailTest.php). Mail approval by randomly chosen members, thresholds from the constitution, expiry, and sending.

- ✅ Picks 20% of members at random, excluding the author, and needs 10% to approve (4 examples: 3 members, 10 members, 11 members, 30 members)
- ✅ Reads instant mail thresholds from the constitution
- ✅ Emails the chosen approvers without the mail content
- ✅ Sends instant mail once approved and posts it as a bulletin
- ✅ Does not send before enough approvals
- ✅ Rejects the mail as soon as it can no longer pass
- ✅ Refuses decisions from non-approvers, the author, and repeat decisions
- ✅ Expires overdue mail and refuses late decisions
- ✅ Lets only the author withdraw pending mail
- ✅ Refuses mail when there are not enough other members
- ✅ Refuses mail to a list nobody on which has an email
- ✅ Turns a bulletin into mail that every other member approves, needing 50% (3 examples: 3 members, 4 members, 5 members)
- ✅ Sends bulletin mail once half the union approves, without a new bulletin
- ✅ Only lets the bulletin author send it, once
- ✅ Writes, approves and shows instant mail through the pages
- ✅ Shows a refusal when creating mail fails
- ✅ Hides other unions' mail and rejects their lists
- ✅ Turns a bulletin into mail through the bulletin page
- ✅ Shows the discussion while writing bulletin mail, only to the author
- ✅ Needs a subject and message for bulletin mail
- ✅ Shows bulletins only to members of the same union

### Error messages

[`tests/Feature/ValidationMessagesTest.php`](tests/Feature/ValidationMessagesTest.php). Form errors say what to do, in GOV.UK style.

- ✅ Names the empty box when rich text has no visible text (2 examples: a bulletin, a policy)
- ✅ Says what to do when a form is left empty
- ✅ Asks for a correct email address and password when logging in fails

### Vouching bulletin

[`tests/Feature/VouchingBulletinTest.php`](tests/Feature/VouchingBulletinTest.php). The pinned bulletin where tenants waiting to be vouched for can introduce themselves.

- ✅ Gives every union one pinned vouching bulletin
- ✅ Lists the pinned bulletin first
- ✅ Lets tenants waiting to be vouched for read it and reply in text only
- ✅ Marks who has and hasn't been vouched for in the thread
- ✅ Keeps tenants waiting to be vouched for out of every other bulletin
- ✅ Refuses blank and overlong text replies
- ✅ Links to it from the dashboard (2 examples: waiting; member)

### Vouching

[`tests/Feature/VouchingTest.php`](tests/Feature/VouchingTest.php). Bootstrapping a union, vouching thresholds, and the vouching QR code.

- ✅ Keeps vouching closed until enough tenants have signed up
- ✅ Does not count tenants who have not confirmed their email
- ✅ Lets the first tenants vouch for each other to bootstrap the union
- ✅ Enrols a tenant only once they reach the vouches required
- ✅ Reads the vouches required from the current constitution
- ✅ Only lets members vouch once the union is established
- ✅ Refuses invalid vouches
- ✅ Vouches through the QR code page
- ✅ Shows the refusal reason instead of the confirm button
- ✅ Sends guests to log in before vouching
- ✅ Shows a personal QR code on the dashboard once vouching opens
