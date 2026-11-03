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
use App\Models\Role;
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
    public function __construct(private Membership $membership) {}

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
     * Propose a new role, with its responsibilities.
     *
     * @param  list<string>  $responsibilities
     *
     * @throws ReferendumRefused
     */
    public function proposeRole(Contact $proposer, string $title, ?string $description, array $responsibilities, ?string $reason): Referendum
    {
        $this->ensureMember($proposer);

        return DB::transaction(function () use ($proposer, $title, $description, $responsibilities, $reason) {
            $role = $proposer->building->roles()->create([
                'title' => $title,
                'description' => $description,
                'opened_at' => now(),
            ]);

            $role->actions()->createMany(array_map(fn (string $responsibility) => ['title' => $responsibility], $responsibilities));

            return $this->open($proposer, $role, ReferendumKind::Adopt, "Create role: {$title}", $reason);
        });
    }

    /**
     * Propose electing a member to a role.
     *
     * @throws ReferendumRefused
     */
    public function proposeElection(Contact $proposer, Role $role, Contact $candidate, ?string $reason): Referendum
    {
        $this->ensureMember($proposer);
        $this->ensureRoleInForce($proposer, $role);

        if ($candidate->building_id !== $proposer->building_id || ! $candidate->isEnrolled()) {
            throw new ReferendumRefused('Only members of the union can be elected.');
        }

        if ($role->contacts()->whereKey($candidate->id)->exists()) {
            throw new ReferendumRefused("{$candidate->name} already holds this role.");
        }

        $this->ensureNoPendingCandidacy($role, $candidate);

        return DB::transaction(fn () => $this->open($proposer, $role, ReferendumKind::Elect, "Elect {$candidate->name} as {$role->title}", $reason, $candidate));
    }

    /**
     * Propose removing a member from a role.
     *
     * @throws ReferendumRefused
     */
    public function proposeRemoval(Contact $proposer, Role $role, Contact $holder, ?string $reason): Referendum
    {
        $this->ensureMember($proposer);
        $this->ensureRoleInForce($proposer, $role);

        if (! $role->contacts()->whereKey($holder->id)->exists()) {
            throw new ReferendumRefused("{$holder->name} doesn't hold this role.");
        }

        $this->ensureNoPendingCandidacy($role, $holder);

        return DB::transaction(fn () => $this->open($proposer, $role, ReferendumKind::Remove, "Remove {$holder->name} as {$role->title}", $reason, $holder));
    }

    /**
     * Propose removing a member from the union. It needs a reason, and passes
     * with the constitution's removal threshold rather than the usual one.
     *
     * @throws ReferendumRefused
     */
    public function proposeMemberRemoval(Contact $proposer, Contact $member, string $reason): Referendum
    {
        $this->ensureMember($proposer);

        if ($member->is($proposer)) {
            throw new ReferendumRefused('You can\'t propose removing yourself. You can leave the union from your membership page instead.');
        }

        if ($member->building_id !== $proposer->building_id || ! $member->isEnrolled()) {
            throw new ReferendumRefused('Only members of the union can be removed.');
        }

        $building = $proposer->building;

        if ($building->referendums()->undecided()->where('referendable_type', 'building')->where('candidate_id', $member->id)->exists()) {
            throw new ReferendumRefused("There's already a referendum on removing {$member->name}.");
        }

        return DB::transaction(fn () => $this->open(
            $proposer, $building, ReferendumKind::Remove, "Remove {$member->name} from the union", $reason, $member,
            passPercent: $building->rules()->removalPassPercent,
        ));
    }

    /**
     * @throws ReferendumRefused
     */
    public function proposeAbolition(Contact $proposer, Role $role, ?string $reason): Referendum
    {
        $this->ensureMember($proposer);
        $this->ensureRoleInForce($proposer, $role);

        if ($role->referendums()->undecided()->where('kind', ReferendumKind::Repeal)->exists()) {
            throw new ReferendumRefused('There\'s already a referendum to abolish this role.');
        }

        return DB::transaction(fn () => $this->open($proposer, $role, ReferendumKind::Repeal, "Abolish role: {$role->title}", $reason));
    }

    /**
     * A member gives up a role they hold. No vote is needed.
     *
     * @throws ReferendumRefused
     */
    public function stepDown(Contact $holder, Role $role): void
    {
        if (! $role->contacts()->detach($holder->id)) {
            throw new ReferendumRefused('You don\'t hold this role.');
        }
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
    public function withdraw(Contact $contact, Referendum $referendum): void
    {
        // A member nominated for a role can decline by withdrawing it.
        $isCandidate = $referendum->kind === ReferendumKind::Elect && $referendum->candidate_id === $contact->id;

        if ($referendum->proposer_id !== $contact->id && ! $isCandidate) {
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

        if ($subject instanceof Building) {
            // The member may have deleted their account while the vote was open.
            if ($referendum->candidate) {
                $this->membership->remove($referendum->candidate);
            }
        } elseif ($subject instanceof Constitution) {
            $subject->update(['published_at' => now()]);
            $referendum->building->update(['current_constitution_id' => $subject->id]);
        } elseif ($subject instanceof Role) {
            $this->putRoleChangeIntoEffect($referendum, $subject);
        } elseif ($subject instanceof Policy && $referendum->kind === ReferendumKind::Repeal) {
            $subject->update(['repealed_at' => now()]);
        } elseif ($subject instanceof Policy) {
            $subject->update(['published_at' => now()]);

            if ($subject->replaces?->isInForce()) {
                $subject->replaces->update(['superseded_at' => now()]);
            }
        }
    }

    private function putRoleChangeIntoEffect(Referendum $referendum, Role $role): void
    {
        match ($referendum->kind) {
            ReferendumKind::Adopt => $role->update(['published_at' => now()]),
            ReferendumKind::Repeal => DB::transaction(function () use ($role) {
                $role->update(['abolished_at' => now()]);
                $role->contacts()->detach();
            }),
            // The candidate may have left the union, or the role been abolished, while the vote was open.
            ReferendumKind::Elect => $role->isInForce() && $referendum->candidate?->isEnrolled()
                ? $role->contacts()->syncWithoutDetaching([$referendum->candidate_id])
                : null,
            ReferendumKind::Remove => $referendum->candidate_id ? $role->contacts()->detach($referendum->candidate_id) : null,
        };
    }

    /**
     * Open the vote, with a members' discussion, using the thresholds in force now.
     * Everyone but the proposer is emailed, including a member it proposes removing.
     */
    private function open(Contact $proposer, Building|Constitution|Policy|Role $subject, ReferendumKind $kind, string $title, ?string $reason, ?Contact $candidate = null, ?int $passPercent = null): Referendum
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
            'candidate_id' => $candidate?->id,
            'thread_id' => $thread->id,
            'referendable_type' => $subject->getMorphClass(),
            'referendable_id' => $subject->getKey(),
            'kind' => $kind,
            'title' => $title,
            'description' => $reason,
            'quorum_percent' => $rules->referendumQuorumPercent,
            'pass_percent' => $passPercent ?? $rules->referendumPassPercent,
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
     * @throws ReferendumRefused
     */
    private function ensureRoleInForce(Contact $proposer, Role $role): void
    {
        if ($role->building_id !== $proposer->building_id || ! $role->isInForce()) {
            throw new ReferendumRefused('That role isn\'t in force.');
        }
    }

    /**
     * Only one election or removal at a time for each member and role.
     *
     * @throws ReferendumRefused
     */
    private function ensureNoPendingCandidacy(Role $role, Contact $candidate): void
    {
        if ($role->referendums()->undecided()->where('candidate_id', $candidate->id)->exists()) {
            throw new ReferendumRefused("There's already a referendum about {$candidate->name} and this role.");
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
