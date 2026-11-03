# Union mail

Emails from a union to its external contacts (landlords, agents, etc.) go through one of two
approval routes. A member can never send an email on the union's behalf alone.

Every compose form reminds members **never to include personal or private information** (their
own or other tenants' names, flat numbers, phone numbers, health details, etc.) in mail.

## Common rules
* Mail is sent to one of the union's mailing lists. Approvers see the list and every recipient before
  deciding. A list with nobody on it who has an email address can't be used.
* Emails are signed by the union, sent to each recipient separately, and never name the author (see
  `contacts.md`).
* The **author is never an approver** and their own approval doesn't count.
* Percentages are of the union's enrolled members (including the author), rounded **up**, with at
  least 1 approval required. They are fixed when the mail is created.
* Approvers can **approve or reject**. A mail:
  * is **sent** as soon as it has the approvals it needs;
  * is **rejected** as soon as so many approvers have rejected it that it can no longer get enough
    approvals;
  * **expires** if it isn't approved in time;
  * can be **withdrawn** by its author while pending.
* Content is locked once a mail is created, so approvers approve exactly what is sent. To change it,
  the author withdraws it and starts again.
* Approvers get an email telling them a mail needs their approval, with a link. The mail's content is
  not in that email. Pending approvals also show on their dashboard.
* All members can see every mail, its status and progress. Only its approvers can decide.

## Instant mail
For when the union needs to act quickly.

* The author writes a subject and message and picks a mailing list.
* A random selection of members is drawn as approvers: `instant_mail_approver_percent` (default
  **20%**) of members, but never fewer than the approvals needed.
* It needs `instant_mail_approval_percent` (default **10%**) of members to approve.
* It expires after `instant_mail_expiry_hours` (default **48**).
* Once sent, it is also posted as a **bulletin** (by the author) so every member can see what the
  union said.

Examples: 10 members needs 1 approval from 2 random members; 11 members needs 2 from 3; 3 members needs
1 from 1.

## Bulletin mail
For considered messages that the whole union stands behind.

* Begins as a **bulletin** posted on the union's noticeboard.
* The bulletin's author can turn it into a mail by picking a mailing list. The bulletin's title and
  text become the subject and message.
* Every other member is an approver.
* It needs `bulletin_mail_approval_percent` (default **50%**) of members to approve.
* It expires after `bulletin_mail_expiry_hours` (default **168**, 7 days).
* A bulletin can only have one pending or sent mail.

Example: 3 members needs 2 approvals from the other 2.

## Not enough members
If the union doesn't have enough members other than the author to supply the approvals needed, the
mail can't be created.

## Bulletins
A simple noticeboard: members post bulletins (title + rich text), and every member of the union
can read them, newest first. Polls on bulletins come with milestone 2.

Each bulletin has a reply thread holding:
* **email replies** to its mail (see Replies below), marked "Email reply", with the sender's name and
  address and a warning if the sender isn't on the mailing list the mail went to;
* **members' posts**, for discussing the bulletin. These are only ever seen by members and are
  **never emailed**. To answer an email, members write new instant mail or send a bulletin as mail,
  which goes through approval as usual.

## Rich text
Bulletins, instant mail and members' replies are written in TinyMCE (self-hosted under its GPL
licence, bundled by Vite, loaded only on pages with an editor). The editor offers paragraphs, two
heading levels, bold/italic/underline/strikethrough, lists, quotes and links.

The server never trusts the editor: `App\Support\RichText::clean()` keeps only those elements (and
`href` on links, limited to http, https and mailto) before anything is stored, and emails clean the
body again when rendering. The text part of each email is made with `RichText::toPlainText()`.

## Replies
Every mail gets a random `reply_token`. When `INBOUND_MAIL_ADDRESS` is set (e.g.
`replies@example.org`), each email is sent with `Reply-To: replies+<token>@example.org`; the mailbox
must accept plus addresses.

`php artisan union-mail:fetch-replies` (scheduled every minute) reads unseen messages from that
mailbox and, for each one addressed to the token of a **sent** mail:
1. strips the quoted original ("On … wrote:", "-----Original Message-----", `>` lines, etc.) and adds
   the rest to the mail's bulletin as an email reply, as escaped plain text, never the sender's HTML;
2. matches the sender to a contact on the mail's list, if there is one;
3. forwards a full copy, with attachments, to the union's inbox (`buildings.contact_email`), if set.
   Attachments aren't stored in the app; the reply notes how many there were.

Every message is marked as seen once processed, including ones that don't match a sent mail (they're
logged and left in the mailbox). A message ID is only ever added once.

Without `INBOUND_MAIL_ADDRESS`, mail is sent with `Reply-To` the union's inbox, as before.

### Inbound mailbox drivers
Like address lookup, the mailbox sits behind an interface (`App\Services\InboundMail\InboundMailbox`)
chosen by `INBOUND_MAIL_DRIVER`:
* `imap`: polls an IMAP folder with ImapEngine (pure PHP, no `ext-imap`). Settings:
  `INBOUND_IMAP_HOST`, `_PORT` (993), `_ENCRYPTION` (ssl), `_USERNAME`, `_PASSWORD`, `_FOLDER` (INBOX).
* `fake`: in memory, for tests.

A webhook provider (Postmark, Mailgun, Cloudflare Email Routing) can be added later as another driver.

## Thresholds
All of the settings above live in the union's constitution (`constitutions.rules`) and can be changed
by referendum.

## Expiry
`php artisan union-mail:expire` marks overdue mail as expired. The scheduler runs it every
five minutes (`php artisan schedule:work` locally, or a cron entry for `schedule:run` in production),
alongside `union-mail:fetch-replies`.
Decisions on an overdue mail are refused even before the command runs.
