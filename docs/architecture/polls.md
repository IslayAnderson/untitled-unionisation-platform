# Polls

A bulletin's author can add polls to it, so members can decide something the bulletin raises.
Polls can't be added to the vouching bulletin. The code is in `App\Services\PollVoting`.

## Rules
* **Who votes:** members (enrolled tenants) of the bulletin's union. Tenants waiting to be vouched
  for can't vote.
* **Answers:** 2 to 10. The author chooses whether members pick one answer or any number of them.
* **Closing:** the author sets a closing time (7 days from now by default, at most 90 days ahead) and
  can close the poll early from the bulletin. There's no job: a poll is open while `closed_at` is
  empty and `closes_at` is in the future.
* **One vote, final:** a member votes once and can't change it.
* **Results only when it closes:** nobody, including the author, sees the totals while the poll is
  open. Everyone sees the turnout ("2 of 12 members have voted").

## Secret ballot
The app records *that* a member voted, separately from *what* they chose:

| Table | Holds | Timestamps |
|---|---|---|
| `poll_voters` | `poll_id`, `contact_id` (unique) | none |
| `poll_answers.vote_count` | running total per answer | not touched by votes |
| `polls.ballot_count` | number of ballots | not touched by votes |

A vote inserts the voter row and increments the totals through the query builder, so `updated_at`
on the poll and its answers doesn't change. A timestamp would show when each total changed, and
matching that against when each member voted would reveal their choice.

What this doesn't protect against: someone with live database access who records the totals after
every single vote. The guarantee is that the stored data never links a member to their choice.
