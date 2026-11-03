# Demo union

A public, read-only union anyone can look around before starting their own: **Larkspur House**, a
made-up block of 252 flats. It has four sections (A to D), seven floors and nine flats per floor, and
flats are named section, floor, flat: **C503** is section C, floor 5, flat 3.

## Turning it on
* Set `DEMO_ENABLED=true` (`config/demo.php`). When it's off, `/demo` is a 404 and the welcome page
  doesn't link to it.
* Build it with `php artisan demo:reset`. It's rebuilt every day at 04:00 by the scheduler while
  it's enabled, so visitors always see recent dates. `php artisan demo:reset --remove` deletes it.
* `buildings.is_demo` marks the demo union. There's only ever one.

## Visiting
* `/demo` explains the demo. **Enter the demo** (`POST /demo`, throttled) logs the visitor in as the
  tenant of C503, Sam Taylor (`you@demo.invalid`). Everyone shares that one account.
* `/demo/printables` shows the demo union's flyers and posters to anyone, without entering. Every
  join code in the demo (printables and dashboard, via `Building::joinUrl()`) leads to `/demo`
  rather than the demo's sign-up page, which is closed.
* A banner on every page says it's a demo, with a link to leave (log out).
* `App\Http\Middleware\DemoIsReadOnly` (on the `web` group) blocks every request that isn't a GET,
  HEAD or OPTIONS from a demo user, apart from logging out and the `/demo` routes. The visitor is
  sent back with a message saying nothing was saved. So nothing is changed and no email is sent.
* Nobody can join the demo union: its join pages are a 404.
* The admin clean-up tools leave it out, and admins can't warn or delete it from `/admin` (409).

## How it's built
`App\Services\DemoUnion` plays a story through the app's real services (vouching, referendums,
polls, mail approvals) so the data is the same shape as a real union's. It:
1. Removes the old demo (`Cleanup::purge`, with no export or emails).
2. Fakes mail and notifications, and seeds the random numbers so each build is the same.
3. Queues events "N days ago at HH:MM" and plays them in order, setting the clock
   (`Carbon::setTestNow`) to each one. Referendums, polls and mail that are due close as the clock
   passes them, just as the scheduler would close them.
4. Puts the clock back.

The story covers about five months: three founders vouching each other in, around 65 neighbours
joining, five still waiting to be vouched for, landlords and agents with contacts and mailing lists,
bulletins with polls and replies, discussions, 20 referendums (constitution amendments, policies
adopted, revised and repealed, roles created and filled, and every outcome), and mail that was sent
(with email replies from the agent), rejected, expired, withdrawn or is waiting for C503's approval.

Every email address ends in `.invalid` or `.example`, so nothing in the demo can reach a real
inbox. Accounts get random passwords that nobody knows.
