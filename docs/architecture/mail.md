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
A simple noticeboard: members post bulletins (title + plain text), and every member of the union
can read them, newest first. Polls on bulletins come with milestone 2.

## Thresholds
All of the settings above live in the union's constitution (`constitutions.rules`) and can be changed
by referendum.

## Expiry
`php artisan union-mail:expire` marks overdue mail as expired. The scheduler runs it every
five minutes (`php artisan schedule:work` locally, or a cron entry for `schedule:run` in production).
Decisions on an overdue mail are refused even before the command runs.
