# Platform admin

Admins run the platform. They aren't union officers and have no say in any union.

## Access
* `users.is_admin`. It can only be granted or removed from the command line, never in the app:
  `php artisan admin:grant someone@example.org` (add `--revoke` to take it away). The account must
  already exist.
* The admin area is at `/admin` (`admin` middleware, `App\Http\Middleware\EnsureAdmin`). Anyone else
  gets a 404. Admins see an "Admin" link in the header.

## Usage numbers
`/admin` shows platform totals and a row per union. `/admin/unions/{id}` shows the full breakdown:
* **People**: members, tenants waiting to be vouched for, unconfirmed emails, landlords and their
  contacts.
* **Talking**: bulletins (not counting the vouching bulletin), discussions, polls.
* **Mail**: sent and waiting for approval.
* **Decisions**: referendums, ones still being voted on, policies, roles.
* **Last active**: the newest bulletin, discussion, mail, referendum or sign-up.

Admins see **counts only**: never members' names or emails, or anything a union has written. The
union's name and address are shown because they identify the union, not a person.

## Clean-up tools
At `/admin/cleanup` (`App\Services\Cleanup`). Admin accounts are never cleaned up. Every action is
logged with the admin's ID.

| Tool | Covers |
|---|---|
| Unconfirmed accounts | Accounts that didn't confirm their email within **7 days** |
| Unfinished sign-ups | Confirmed accounts that never chose a building and flat after **30 days** |
| Never vouched for | Tenants of unions **with members** still not vouched for after **90 days** |
| Abandoned unions | Unions with no members where nobody has signed up for **90 days** |

### Warn first, delete a week later
Nothing is deleted without telling the account holder first. Each clean-up has two steps:
1. **Email.** The admin emails everyone the tool covers who hasn't been warned yet
   (`App\Notifications\CleanupWarning`). The email says why, and that logging in before the deadline,
   `GRACE_DAYS` (**7**) later, keeps their account. `users.cleanup_warned_at` records when. For a union,
   everyone with an account in it is emailed, `buildings.cleanup_warned_at` is set too, and logging in
   by **any** of them keeps the union.
2. **Delete.** From the deadline, the admin can delete those who haven't been back. An account is
   deleted only if it was warned at least 7 days ago and `last_seen_at` isn't after the warning; a
   union only if none of its account holders has been seen since its warning.

`App\Listeners\RecordAccountActivity` listens for `Login` and `Authenticated` (so a remembered or
still-open session counts as coming back). It updates `users.last_seen_at` at most hourly and clears
the account's warning, and its union's. Warning a union also warns each of its account holders, so the
listener knows to clear the union's warning.

Deleting an account deletes its user, sessions and contact (and its vouches, voting records and roles
via cascades). Anything they wrote stays, shown as by a former member.

### Deleting a single union
From a union's page, the admin types its name to email its members, then after the week, if nobody
has logged in, types it again to delete it. "Don't delete" calls it off (members aren't told). Deleting a
union (`deleteUnion`) removes, in one transaction: the building and everything that cascades from it
(units, constitutions, policies, roles, referendums, bulletins, polls, threads, mail, mailing lists,
landlords, uploaded image records), replies on its bulletins and threads (a morph, so not cascaded),
its tenants' users and sessions, and all its contacts, including landlords' staff. The stored image and
attachment files are deleted after the transaction commits.

Unused uploaded images are not cleaned up: see `uploads.md` on preserving images Cloudflare flags.
