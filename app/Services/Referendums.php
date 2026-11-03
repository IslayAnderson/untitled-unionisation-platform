<?php

namespace App\Services;

use App\Enums\ReferendumAnswer;
use App\Enums\ReferendumKind;
use App\Enums\ReferendumOutcome;
use App\Exceptions\ReferendumRefused;
use App\Models\Building;
use App\Models\Constitution;
use App\Models\Contact;
use App\Models\Policy;
use App\Models\Referendum;
use App\Models\ReferendumVoter;
use App\Models\User;
use App\Notifications\ReferendumDecided;
use App\Notifications\ReferendumOpened;
use App\Support\ConstitutionRules;
use App\Support\RichText;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * Proposing, voting in and counting referendums, and putting the result into
 * effect. Rules are in docs/architecture/governance.md.
 */
class Referendums
{
    /**
     * Propose a new version of the constitution.
     *
     * @throws ReferendumRefused
     */
    public function proposeConstitution(Contact $proposer, string $title, ?string $reason, string $richText, ConstitutionRules $rules): Referendum
    {
        $this->ensureMember($proposer);
        $building = $proposer->building;

        if ($building->referendums()->undecided()->where('referendable_type', 'constitution')->exists()) {
            throw new ReferendumRefused('There\'s already a referendum on the constitution. Wait until it has been decided.');
        }

        return DB::transaction(function () use ($proposer, $building, $title, $reason, $richText, $rules) {
            $draft = $building->constitutions()->create([
                'title' => $building->currentConstitution?->title ?? "Constitution of {$building->name}",
                'rich_text' => $richText,
                'rules' => $rules->toArray(),
                'opened_at' => now(),
            ]);

            return $this->open($proposer, $draft, ReferendumKind::Adopt, $title, $reason);
        });
    }

    /**
     * Propose a new policy, or a revision of one in force.
     *
     * @throws ReferendumRefused
     */
    public function proposePolicy(Contact $proposer, string $title, ?string $reason, string $richText, ?Policy $replaces = null): Referendum
    {
        $this->ensureMember($proposer);

        if ($replaces) {
            $this->ensureCanChange($proposer, $replaces);
        }

        return DB::transaction(function () use ($proposer, $title, $reason, $richText, $replaces) {
            $policy = $proposer->building->policies()->create([
                'replaces_policy_id' => $replaces?->id,
                'title' => $title,
                'rich_text' => $richText,
                'opened_at' => now(),
            ]);

            return $this->open($proposer, $policy, ReferendumKind::Adopt, ($replaces ? 'Revise policy: ' : 'Adopt policy: ').$title, $reason);
        });
    }

    /**
     * @throws ReferendumRefused
     */
    public function proposeRepeal(Contact $proposer, Policy $policy, ?string $reason): Referendum
    {
        $this->ensureMember($proposer);
        $this->ensureCanChange($proposer, $policy);

        return DB::transaction(fn () => $this->open($proposer, $policy, ReferendumKind::Repeal, "Repeal policy: {$policy->title}", $reason));
    }

    /**
     * Record a secret ballot: who voted and the totals are written separately,
     * without timestamps. Votes can't be changed.
     *
     * @throws ReferendumRefused
     */
    public function vote(Contact $voter, Referendum $referendum, ReferendumAnswer $answer): void
    {
        $this->ensureMember($voter);

        if ($referendum->building_id !== $voter->building_id) {
            throw new ReferendumRefused('Only members of the union can vote.');
        }

        DB::transaction(function () use ($voter, $referendum, $answer) {
            $referendum = Referendum::lockForUpdate()->findOrFail($referendum->id);

            if (! $referendum->isOpen()) {
                throw new ReferendumRefused('Voting in this referendum has closed.');
            }

            try {
                ReferendumVoter::create(['referendum_id' => $referendum->id, 'contact_id' => $voter->id]);
            } catch (UniqueConstraintViolationException) {
                throw new ReferendumRefused('You\'ve already voted in this referendum.');
            }

            // The query builder, so updated_at isn't touched: it would record when each vote was cast.
            DB::table('referendums')->where('id', $referendum->id)->incrementEach([
                $answer->countColumn() => 1,
                'ballot_count' => 1,
            ]);
        });
    }

    /**
     * @throws ReferendumRefused
     */
    public function withdraw(Contact $proposer, Referendum $referendum): void
    {
        if ($referendum->proposer_id !== $proposer->id) {
            throw new ReferendumRefused('Only the member who proposed this can withdraw it.');
        }

        $updated = Referendum::whereKey($referendum->id)->open()->update([
            'closed_at' => now(),
            'outcome' => ReferendumOutcome::Withdrawn,
        ]);

        if (! $updated) {
            throw new ReferendumRefused('Voting in this referendum has already closed.');
        }
    }

    /**
     * Count every referendum whose voting period has ended, optionally only one union's.
     */
    public function closeDue(?Building $building = null): int
    {
        return Referendum::undecided()
            ->where('closes_at', '<=', now())
            ->when($building, fn ($query) => $query->where('building_id', $building->id))
            ->pluck('id')
            ->filter(fn (int $id) => $this->close(Referendum::findOrFail($id)))
            ->count();
    }

    /**
     * Count a referendum whose voting period has ended and put the result into
     * effect. Returns false if it wasn't due.
     */
    public function close(Referendum $referendum): bool
    {
        return DB::transaction(function () use ($referendum) {
            $referendum = Referendum::lockForUpdate()->findOrFail($referendum->id);

            if (! $referendum->isDue()) {
                return false;
            }

            $memberCount = $referendum->building->enrolledCount();
            $decisive = $referendum->for_count + $referendum->against_count;

            $outcome = match (true) {
                $referendum->ballot_count < $referendum->quorumFor($memberCount) => ReferendumOutcome::NoQuorum,
                $referendum->for_count * 100 > $referendum->pass_percent * $decisive => ReferendumOutcome::Passed,
                default => ReferendumOutcome::Rejected,
            };

            $referendum->update([
                'closed_at' => now(),
                'outcome' => $outcome,
                'member_count' => $memberCount,
            ]);

            if ($outcome === ReferendumOutcome::Passed) {
                $this->putIntoEffect($referendum);
            }

            Notification::send($this->memberUsers($referendum->building), new ReferendumDecided($referendum));

            return true;
        });
    }

    private function putIntoEffect(Referendum $referendum): void
    {
        $subject = $referendum->referendable;

        if ($subject instanceof Constitution) {
            $subject->update(['published_at' => now()]);
            $referendum->building->update(['current_constitution_id' => $subject->id]);
        } elseif ($subject instanceof Policy && $referendum->kind === ReferendumKind::Repeal) {
            $subject->update(['repealed_at' => now()]);
        } elseif ($subject instanceof Policy) {
            $subject->update(['published_at' => now()]);

            if ($subject->replaces?->isInForce()) {
                $subject->replaces->update(['superseded_at' => now()]);
            }
        }
    }

    /**
     * Open the vote, with a members' discussion, using the thresholds in force now.
     */
    private function open(Contact $proposer, Constitution|Policy $subject, ReferendumKind $kind, string $title, ?string $reason): Referendum
    {
        $building = $proposer->building;
        $rules = $building->rules();

        $thread = $building->threads()->create([
            'author_id' => $proposer->id,
            'title' => "Referendum: {$title}",
            'rich_text' => RichText::fromPlainText($reason ?: 'Discuss this referendum here.'),
            'published_at' => now(),
        ]);

        $referendum = $building->referendums()->create([
            'proposer_id' => $proposer->id,
            'thread_id' => $thread->id,
            'referendable_type' => $subject->getMorphClass(),
            'referendable_id' => $subject->getKey(),
            'kind' => $kind,
            'title' => $title,
            'description' => $reason,
            'quorum_percent' => $rules->referendumQuorumPercent,
            'pass_percent' => $rules->referendumPassPercent,
            'closes_at' => now()->addDays($rules->referendumDays),
        ]);

        Notification::send($this->memberUsers($building, except: $proposer), new ReferendumOpened($referendum));

        return $referendum;
    }

    /**
     * A revision or repeal needs the policy to be in force, with no other
     * change to it being voted on.
     *
     * @throws ReferendumRefused
     */
    private function ensureCanChange(Contact $proposer, Policy $policy): void
    {
        if ($policy->building_id !== $proposer->building_id || ! $policy->isInForce()) {
            throw new ReferendumRefused('Only policies in force can be revised or repealed.');
        }

        $pending = Referendum::undecided()
            ->where('referendable_type', 'policy')
            ->where(fn ($query) => $query
                ->where('referendable_id', $policy->id)
                ->orWhereIn('referendable_id', $policy->revisions()->select('id')))
            ->exists();

        if ($pending) {
            throw new ReferendumRefused('There\'s already a referendum to change this policy. Wait until it has been decided.');
        }
    }

    /**
     * @return Collection<int, User>
     */
    private function memberUsers(Building $building, ?Contact $except = null): Collection
    {
        return $building->contacts()->confirmedTenants()->enrolled()
            ->when($except, fn ($query) => $query->whereKeyNot($except->id))
            ->with('user')->get()->pluck('user')->filter();
    }

    /**
     * @throws ReferendumRefused
     */
    private function ensureMember(Contact $contact): void
    {
        if (! $contact->isEnrolled()) {
            throw new ReferendumRefused('Only members can take part in referendums.');
        }
    }
}
