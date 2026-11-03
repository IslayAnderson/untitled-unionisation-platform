# Governance: constitution, policies and referendums

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

A policy can only be revised or repealed while it's in force, and only one change to it can be voted
on at a time. Any member can propose; tenants waiting to be vouched for can read the constitution but
can't see policies or referendums.

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
| `instant_mail_approval_percent` | 10 | 1–100 |
| `instant_mail_approver_percent` | 20 | 1–100 |
| `instant_mail_expiry_hours` | 48 | 1–336 |
| `bulletin_mail_approval_percent` | 50 | 1–100 |
| `bulletin_mail_expiry_hours` | 168 | 24–720 |

A constitution without a rule (for example `referendum_days`, added after launch) uses the default.
The text and the numbers are edited separately, so the amendment form reminds members to change both.
