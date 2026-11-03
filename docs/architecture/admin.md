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
At `/admin/cleanup` (`App\Services\Cleanup`). Each tool shows how many it would delete and asks for
confirmation. Admin accounts are never deleted. Every action is logged with the admin's ID.

| Tool | Deletes |
|---|---|
| Unconfirmed accounts | Accounts that didn't confirm their email within **7 days** |
| Unfinished sign-ups | Confirmed accounts that never chose a building and flat after **30 days** |
| Never vouched for | Tenants of unions **with members** still not vouched for after **90 days** |
| Abandoned unions | Unions with no members where nobody has signed up for **90 days** |

Deleting an account deletes its user, sessions and contact (and vouches, votes cast records and roles
via cascades). Anything they wrote stays, shown as by a former member.

A union can also be deleted from its page by typing its name. Deleting a union (`deleteUnion`) removes,
in one transaction: the building and everything that cascades from it (units, constitutions, policies,
roles, referendums, bulletins, polls, threads, mail, mailing lists, landlords, uploaded image records),
replies on its bulletins and threads (a morph, so not cascaded), its tenants' users and sessions, and
all its contacts, including landlords' staff. The stored image and attachment files are deleted after
the transaction commits.

Unused uploaded images are not cleaned up: see `uploads.md` on preserving images Cloudflare flags.
