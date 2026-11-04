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
* The bulletin's author can turn it into a mail. The compose page starts with the bulletin's title
  and text as the subject and message, and shows the bulletin's replies, so the author can rework the
  mail to reflect the discussion before asking for approval. The bulletin itself is unchanged; the
  mail links back to it.
* Approvers approve the mail as written, and it's locked from then on like all union mail.
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
can read them, pinned bulletins first, then newest first. A bulletin's author can add secret-ballot polls to it (see `polls.md`).
Each union also has a pinned vouching bulletin that tenants waiting to be vouched for can read and
reply to (see `signup-process.md`).

Each bulletin has a reply thread holding:
* **email replies** to its mail (see Replies below), marked "Email reply", with the sender's name and
  address and a warning if the sender isn't on the mailing list the mail went to;
* **members' posts**, for discussing the bulletin. These are only ever seen by members and are
  **never emailed**. To answer an email, members write new instant mail or send a bulletin as mail,
  which goes through approval as usual.

## Discussions
Threads (shown as "Discussions") are for members to talk things through. Any member can start one,
with a title and rich text, and members reply in rich text. Only members of the union can read them,
and they're never emailed, so they have no mail or polls. The list shows the most recently active
first. Replies use the same `replies` table as bulletins (`replyable_type` = `thread`).

## Editing and deleting
Authors can edit or delete their own posts until something depends on them:
* a **bulletin** until it has a reply, a poll or any mail (the vouching bulletin can't be changed);
* a **discussion** until it has a reply (a referendum's discussion can't be changed);
* a member's **reply** until someone replies after it. Email replies can't be changed. Tenants
  waiting to be vouched for edit theirs as plain text, as they wrote it.

Each model's `canBeChangedBy()` holds the rule. Deleting is permanent; edits aren't marked, since
nobody has responded to the old version.

## Rich text
Bulletins, instant mail and members' replies are written in TinyMCE (self-hosted under its GPL
licence, bundled by Vite, loaded only on pages with an editor). The editor offers paragraphs, two
heading levels, bold/italic/underline/strikethrough, lists, quotes, links and images. Images are
compressed, stripped of metadata and scanned for CSAM: see `uploads.md`.

The server never trusts the editor: `App\Support\RichText::clean()` keeps only those elements (with
`href` on links, limited to http, https and mailto, and `src`/`alt` on images from our own uploads)
before anything is stored, and emails clean the
body again when rendering. The text part of each email is made with `RichText::toPlainText()`.

## Attachments
The author of instant or bulletin mail can attach files when they write it (`App\Services\MailAttachments`):
* Up to **5 files**, **10 MB** each, **20 MB** in total (`config/uploads.php`). PHP's
  `upload_max_filesize` and `post_max_size` must allow this in production.
* PDF, Word, OpenDocument, RTF, Excel, CSV and text files, and JPEG, PNG, GIF and WebP images. The type
  is checked from the file's contents, not its name.
* **Images** go through the same pipeline as images in rich text: compressed, stripped of metadata
  (including GPS location), stored on the public uploads disk and CSAM-scanned (see `uploads.md`). The
  attachment keeps the original name with the new extension (`photo.png` → `photo.jpg`).
* **Documents** are stored on the private `ATTACHMENTS_DISK` (default `local`, i.e. `storage/app/private`)
  exactly as uploaded. They can carry metadata such as the author's name, which would undo the rule that
  mail never names its author, so the form warns the author to check.

Attachments are fixed once the mail is created, like the subject and message. Members can download them
from the mail's page (`GET /mail/{mail}/attachments/{attachment}`), so approvers can check them before
deciding; they're sent with every copy of the mail. If creating the mail fails part way, files already
stored are deleted. If the form is sent back with errors, the author has to choose the files again.

## Replies
Every mail gets a random `reply_token`. When `INBOUND_MAIL_ADDRESS` is set (e.g.
`replies@example.org`), each email is sent with `Reply-To: replies+<token>@example.org`; the mailbox
must accept plus addresses.

`php artisan union-mail:fetch-replies` (scheduled every minute) reads unseen messages from that
mailbox and, for each one addressed to the token of a **sent** mail:
1. strips the quoted original ("On … wrote:", "-----Original Message-----", `>` lines, etc.) and adds
   the rest to the mail's bulletin as an email reply, as escaped plain text, never the sender's HTML;
2. matches the sender to a contact on the mail's list, if there is one;
3. forwards a full copy, with attachments, to the union's inbox (`buildings.contact_email`). If the
   union hasn't set one, it emails the copy to every member separately (to their login address), so
   nobody sees the others' addresses and the attachments still reach the union.
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

## Email template
Every HTML email the platform sends uses one template: Laravel's mail components, published to
`resources/views/vendor/mail` and restyled like the site (Arial, GOV.UK colours, a black header bar and a
green button), with the theme in `html/themes/default.css`, which Laravel inlines.
* **Notifications** (`resources/views/vendor/notifications/email.blade.php`) are headed with the platform's
  name and have its footer: who runs it, and links to the privacy policy and feedback form. There's no
  "Hello!" or "Regards" unless a notification sets one. Laravel's email confirmation is reworded in
  `AppServiceProvider`.
* **Union mail** (`emails/union-message`) is headed and signed by the union, with no link to or mention of
  the platform. Its rich text body is passed as HTML (`:html`), not parsed as Markdown.
* **Forwarded replies** (`emails/forwarded-reply`) are headed with the union's name. The sender's message
  is escaped plain text, passed as HTML so Markdown in it isn't turned into links.

`php artisan mail:samples you@example.org` sends one of every email, with made-up details, to check how
they look in a real inbox. Nothing is saved, so links in them lead nowhere.

