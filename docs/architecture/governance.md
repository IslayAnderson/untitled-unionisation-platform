# Governance: constitution, policies, roles and referendums

Each union decides its rules by **referendum**: a secret vote of all members. Everyday decisions use
polls on bulletins instead (see `polls.md`). The code is in `App\Services\Referendums`.

## What can be decided
* **Amending the constitution.** A member edits the whole constitution (rich text) and its numbers
  (`constitutions.rules`, see below). This creates a draft `constitutions` row (`opened_at` set,
  `published_at` empty). If it passes, it's published and becomes the building's
  `current_constitution_id`, so the platform follows the new numbers straight away. Only one
  constitution referendum can be undecided at a time, so versions can't conflict.
* **Adopting a policy.** A policy is a position or rule the union agrees, such as its demands or how it
  runs meetings. A proposed policy is a `policies` row with no `published_at`; it's set if the
  referendum passes.
* **Revising a policy.** A new `policies` row with `replaces_policy_id`. If it passes, the old policy
  gets `superseded_at`.
* **Repealing a policy.** A referendum of kind `repeal` on the policy. If it passes, the policy gets
  `repealed_at`.

* **Roles** (`App\Models\Role`, such as secretary or repairs coordinator), each by referendum:
  * *creating* one: a `roles` row with its responsibilities (`role_actions`), published if it passes;
  * *electing* a member (kind `elect`, `candidate_id`): they're added to `contact_role` if it passes,
    as long as the role is still in force and they're still a member;
  * *removing* a member (kind `remove`);
  * *abolishing* it (kind `repeal`): it gets `abolished_at` and everyone holding it stops.

  A role can have several holders. A member can step down at any time without a vote, and a nominee
  can decline by withdrawing their election. Only one election or removal per member and role can be
  undecided at a time. Roles carry only the responsibilities members give them, not platform
  permissions.

* **Removing a member from the union** (kind `remove`, with the union's building as the referendable
  and the member as `candidate_id`), proposed at `/members/remove`. A reason is required, since the
  member reads it too. Nobody can propose removing themselves (they can leave instead), and only one
  removal per member can be undecided. It passes with the constitution's `removal_pass_percent`
  (default 50) instead of the usual threshold, with the usual quorum. The member is emailed like
  everyone else, sees it on their dashboard, can reply in the discussion and can vote. See
  "Leaving and removal" below for what happens if it passes.

A policy can only be revised or repealed while it's in force, and only one change to it can be voted
on at a time. Any member can propose; tenants waiting to be vouched for can read the constitution but
can't see policies or referendums.

## Leaving and removal
`App\Services\Membership`. Membership ends in three ways:
* **Leaving.** A member leaves from `/membership` (linked from the dashboard), without a vote. They
  stay a tenant with an account, so neighbours can vouch for them again if they want to rejoin.
* **Removal** by referendum (above). `contacts.removed_at` is set, and from then on they can't be
  vouched for again and can't read the vouching bulletin. It's set even if they left while the vote
  was open.
* **Deleting their account** (`DELETE /account`, needs their password). Anyone can, member or not.
  Their user and contact are deleted; what they wrote stays, shown as by a former member.

Whichever way, a member's membership ends the same way: `enrolled_at` is cleared, the vouches they
received are deleted (so rejoining means being vouched for again; vouches they gave stand), they
stop holding roles, any open nomination of them to a role is withdrawn, and their undecided approvals
on pending mail are dropped (mail that can no longer get enough approvals is rejected). Their votes
and posts stay. They're then emailed a copy of the union's records, as they are that moment
(`App\Jobs\SendUnionExport`; see `admin.md`). A member who left before being removed isn't sent a
second copy. Tenants who weren't members aren't sent one.

The constitution's section 3 describes leaving and removal, with the `{removal_pass_percent}`
placeholder. Removed and departed tenants count as signed-up tenants but not members, so admin
clean-up treats them like any tenant who isn't a member.

## How a referendum runs
* Opening it starts a members' discussion (a thread titled "Referendum: …" with the proposer's reason)
  and emails every other member a link. The email doesn't say what's proposed.
* The quorum and pass thresholds are copied from the constitution **when it opens**, so an amendment
  can't change the rules of a vote already under way. It stays open for `referendum_days`.
* Members vote **for**, **against** or **abstain**, once, secretly. As with polls, `referendum_voters`
  records who voted (no timestamps) and the referendum holds anonymous totals, incremented without
  touching `updated_at`. Totals are hidden until voting closes; turnout and the quorum are shown.
* The proposer can withdraw it while voting is open. Nothing changes and votes aren't shown.
* `referendums:close` runs every minute and counts referendums past their deadline. Pages also count a
  referendum when someone opens it after its deadline.

## Counting
* `member_count` is the number of members when it's counted.
* **Quorum:** at least `quorum_percent` of members must vote (abstentions count), rounded up, and at
  least one.
* **Pass:** strictly more than `pass_percent` of the for and against votes are for. A tie fails, and so
  does a vote where everyone abstained.
* `outcome` is `passed`, `rejected`, `no_quorum` or `withdrawn`.
* Once counted, every member is emailed a link to the result. The email doesn't say what was decided.
  Withdrawn referendums aren't emailed.

## Showing changes
The referendum page compares the proposal with the version in force when it was proposed: changed
numbers side by side, and the text paragraph by paragraph (`App\Support\TextDiff`, on plain text).

## The numbers
`App\Support\ConstitutionRules::FIELDS` lists each number, its label and the range an amendment can set:

| Rule | Default | Range |
|---|---|---|
| `min_signups` | 3 | 2–50 |
| `vouches_required` | 2 | 1–10 |
| `referendum_quorum_percent` | 50 | 1–100 |
| `referendum_pass_percent` | 50 | 50–99 |
| `referendum_days` | 14 | 1–60 |
| `removal_pass_percent` | 50 | 50–99 |
| `instant_mail_approval_percent` | 10 | 1–100 |
| `instant_mail_approver_percent` | 20 | 1–100 |
| `instant_mail_expiry_hours` | 48 | 1–336 |
| `bulletin_mail_approval_percent` | 50 | 1–100 |
| `bulletin_mail_expiry_hours` | 168 | 24–720 |

A constitution without a rule (for example `referendum_days`, added after launch) uses the default.

The text refers to the numbers with placeholders such as `{vouches_required}`
(`App\Support\ConstitutionText`), filled in wherever the constitution is shown, so the text always
matches the numbers the platform follows. Amending a number changes the text, and the referendum's
text comparison shows it. The amendment form lists the placeholders. Unknown placeholders are shown
as typed. Constitutions created before placeholders existed have the numbers written into the text.
