# Knowledge base: commands

How to run the platform's own Artisan commands, the jobs that keep it running, how to redraw the
architecture diagrams, and useful Tinker snippets for looking into problems. For how each feature
works, see [docs/architecture](../architecture).

## Running a command

Locally, from the project folder:

```bash
php artisan <command>
```

On the Plesk server, run commands from the site's `httpdocs` folder as the site's own user, never as
root, so any files they write keep the right owner. Use Plesk's PHP 8.3:

```bash
sudo -u "$(stat -c %U .)" /opt/plesk/php/8.3/bin/php artisan <command>
```

Every example below shows the local form. On the server, swap `php artisan` for the line above.
Add `--help` to any command to see its arguments and options.

## The platform's commands

| Command | What it does | Runs on its own? |
| --- | --- | --- |
| [`admin:create`](#admincreate) | Makes a new admin account | No |
| [`admin:grant`](#admingrant) | Gives or takes away admin access | No |
| [`mail:samples`](#mailsamples) | Emails you one of every email the platform sends | No |
| [`demo:reset`](#demoreset) | Rebuilds or removes the demo union | Daily at 04:00, when the demo is on |
| [`union-mail:fetch-replies`](#union-mailfetch-replies) | Adds landlords' email replies to bulletins | Every minute |
| [`union-mail:expire`](#union-mailexpire) | Expires union mail that wasn't approved in time | Every 5 minutes |
| [`referendums:close`](#referendumsclose) | Counts finished referendums and puts passed ones into effect | Every minute |
| [`union-exports:prune`](#union-exportsprune) | Deletes union exports once their links expire | Daily |

The scheduled ones only run if the scheduler is set up: see [Scheduler and queues](#scheduler-and-queues).
You can still run any of them by hand, and nothing breaks if one runs twice.

### admin:create

Makes an admin account that isn't a tenant of any union. Use it to set up the first admin on a new
server. It asks for the password twice and never takes it as an argument, so the password doesn't
end up in your shell history. The email counts as confirmed, and logging in goes straight to `/admin`.

```bash
php artisan admin:create someone@example.org
```

```
 Password:
 >
 Confirm the password:
 >
someone@example.org is now an admin. Log in at https://example.org/login.
```

If the address already has an account, it stops and tells you to use `admin:grant` instead. A weak or
mismatched password is refused with the reason.

### admin:grant

Gives an existing account admin access. Add `--revoke` to take it away. Admin access can only change
from the command line, never in the app.

```bash
php artisan admin:grant someone@example.org
```

```
someone@example.org is now an admin.
```

```bash
php artisan admin:grant someone@example.org --revoke
```

```
someone@example.org is no longer an admin.
```

### mail:samples

Sends a copy of every email the platform can send to one address, so you can check how they look in a
real inbox, or check mail is being delivered at all. The emails are about a made-up union, "Sample
Court Tenants' Union", and are sent straight away rather than queued, so you don't need a queue
worker running. Nothing is saved.

```bash
php artisan mail:samples you@example.org
```

```
Sent: Union mail to a landlord
Sent: Mail needs approval
Sent: Referendum opened
…
Sent: Confirm your email address
Sent every sample email to you@example.org.
```

If nothing arrives, the problem is the mail settings (`MAIL_*` in `.env`) or the mail server, not
the platform. With `MAIL_MAILER=log` they go to `storage/logs/laravel.log` instead. The same emails
can be seen in a browser at `/demo/emails` while the demo is on.

### demo:reset

Builds the public demo union, Larkspur House Tenants' Union, with its history dated from now. If
there's already a demo union, it's replaced. Add `--remove` to delete the demo without building a new one.

```bash
php artisan demo:reset
```

```
Built Larkspur House Tenants' Union: 67 members, 7 waiting, 19 bulletins, 21 referendums.
```

```bash
php artisan demo:reset --remove
```

```
Removed the demo union.
```

The demo can only be seen when `DEMO_ENABLED=true`. If it isn't, the command reminds you. While the
demo is on, the scheduler rebuilds it every day at 04:00 so its open votes stay open. See
[demo.md](../architecture/demo.md).

### union-mail:fetch-replies

Reads new messages from the replies inbox and adds each landlord's reply to the bulletin for the mail
it answers. Their quoted history is removed, and HTML is turned into plain text. A copy of each reply,
with any attachments, is forwarded to the union's inbox, or to every member if the union has none.

```bash
php artisan union-mail:fetch-replies
```

```
Added 2 replies.
```

It does nothing until the replies inbox is set up (`INBOUND_MAIL_ADDRESS` and the `INBOUND_IMAP_*`
settings), and says so:

```
Inbound mail is not set up. Set INBOUND_MAIL_ADDRESS to fetch replies.
```

Run it by hand to test the inbox settings: an error here usually means a wrong IMAP host, port or
password. See [mail.md](../architecture/mail.md).

### union-mail:expire

Marks union mail that didn't get enough approvals before its deadline as expired, so it can't be
sent.

```bash
php artisan union-mail:expire
```

```
Expired 1 mail.
```

### referendums:close

Counts every referendum whose voting has ended. Passed ones take effect: the constitution is amended,
a policy adopted, revised or repealed, a role created or abolished, or a member elected or removed.
Members are emailed the result.

```bash
php artisan referendums:close
```

```
Counted 1 referendum.
```

If a referendum shows as still open after its closing time, the scheduler probably isn't running.
Running this by hand counts it.

### union-exports:prune

Deletes union exports (the zips emailed to members when they leave or a union is deleted) once their
download links have expired, after 30 days.

```bash
php artisan union-exports:prune
```

```
Deleted 3 exports.
```

## Scheduler and queues

### The scheduler

The scheduled commands need one cron job, run every minute as the site user. In Plesk, add it under
Websites & Domains → Scheduled Tasks:

```bash
cd /path/to/httpdocs && /opt/plesk/php/8.3/bin/php artisan schedule:run
```

Locally, keep the scheduler running in a terminal instead:

```bash
php artisan schedule:work
```

To see what's scheduled and when each command next runs:

```bash
php artisan schedule:list
```

### Queue workers

Most emails, and image cache warming, are queued (`QUEUE_CONNECTION=database`), so a worker has to be
running for them to go out:

```bash
php artisan queue:work
```

Union exports and deletions take longer, so they use their own connection and need a second worker:

```bash
php artisan queue:work database-long --timeout=3600
```

On the server, keep both running with a scheduled task or a process manager. After deploying new
code, restart them so they pick it up:

```bash
php artisan queue:restart
```

When jobs fail, list them, then retry them once the cause is fixed:

```bash
php artisan queue:failed
```

```bash
php artisan queue:retry all
```

## Laravel's own commands you'll need

After deploying (the Plesk Laravel Toolkit can run these for you):

```bash
php artisan migrate --force
```

```bash
php artisan optimize:clear
```

Run `optimize:clear` after changing `.env` too, or the old settings stay cached. Locally, uploaded
images need the public storage link, made once:

```bash
php artisan storage:link
```

To check a setting as the app sees it:

```bash
php artisan config:show mail
```

## Redrawing the diagrams

The diagrams in [docs/architecture](../architecture) are drawn by scripts in
[docs/architecture/diagrams](../architecture/diagrams). They need Python 3 and nothing else.

**After changing the database**, redraw the entity diagram so it matches:

```bash
python3 docs/architecture/diagrams/entities.py
```

```
Drew 28 tables and 52 relationships in docs/architecture/Entity Diagram.drawio
```

It runs the migrations into a throwaway SQLite database, so your own database isn't touched. A new
table has to be given a place in the diagram first. If it hasn't been, the script stops and names it:

```
Add these tables to COLUMNS in entities.py: rent_strikes
```

Add the table to the column for its area in `COLUMNS`, then run the script again.

**After changing a process**, edit its section in `processes.py`, then redraw them all:

```bash
python3 docs/architecture/diagrams/processes.py
```

Each diagram is laid out by hand in the script: a step, decision, Object box or note at a position,
then the arrows between them. `make.py` holds the shapes. Running the script rewrites every diagram,
so make changes in the script rather than in draw.io, or they'll be lost the next time it runs.
Open a diagram in draw.io (or export it with
`/Applications/draw.io.app/Contents/MacOS/draw.io -x -f png "docs/architecture/signup process.drawio"`)
to check the result.

## Tinker

Tinker runs PHP inside the app, against the real database. It's for looking into problems. Prefer the
commands above where one exists, and be careful with anything that changes data: there's no undo.

Start it with:

```bash
php artisan tinker
```

Then type a line and press Enter. Each snippet below can also be run in one go with `--execute`. Use
single quotes outside and double quotes inside:

```bash
php artisan tinker --execute 'echo App\Models\User::count();'
```

### Send one test email

The quickest check that mail is being delivered. `mail:samples` sends every real email instead.

```php
Mail::raw('Test from the platform.', fn ($message) => $message->to('you@example.org')->subject('Test'));
```

If this throws an error, it names the problem: a refused connection, a failed login, a rejected
sender. If it succeeds but nothing arrives, look at the mail server's logs.

### See which mail settings are in use

```php
[config('mail.default'), config('mail.mailers.smtp.host'), config('mail.mailers.smtp.port'), config('mail.from.address')];
```

If these don't match `.env`, run `php artisan optimize:clear`.

### Look up an account

```php
User::where('email', 'someone@example.org')->first(['id', 'email', 'email_verified_at', 'is_admin', 'contact_id']);
```

`email_verified_at` is `null` if they never confirmed their email. `contact_id` is `null` if they never
chose their building and flat.

### Resend someone's confirmation email

For a tenant who says the email never came:

```php
User::where('email', 'someone@example.org')->first()->sendEmailVerificationNotification();
```

### Confirm someone's email by hand

Only once you're sure the address is theirs, for example if they've emailed you from it:

```php
User::where('email', 'someone@example.org')->first()->markEmailAsVerified();
```

### Look at a union

```php
$union = App\Models\Building::where('name', 'like', '%Larkspur%')->first();
[$union->name, $union->enrolledCount(), $union->confirmedTenantCount()];
```

This gives its name, its number of members, and the number of tenants who have confirmed their
email, members included. The admin area at `/admin/unions/{id}` shows the same without Tinker.

### Count the platform

```php
[User::count(), App\Models\Building::count(), App\Models\User::whereNull('email_verified_at')->count()];
```

This gives the number of accounts, unions, and accounts with unconfirmed emails.
