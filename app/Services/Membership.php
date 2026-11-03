<?php

namespace App\Services;

use App\Enums\ExportReason;
use App\Enums\ReferendumKind;
use App\Enums\ReferendumOutcome;
use App\Enums\UnionMailStatus;
use App\Exceptions\MembershipRefused;
use App\Jobs\SendUnionExport;
use App\Models\Contact;
use App\Models\Referendum;
use App\Models\UnionMail;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * How membership ends: a member leaves, members remove them by referendum, or
 * a tenant deletes their account. Rules are in docs/architecture/governance.md.
 */
class Membership
{
    /**
     * A member leaves the union. They stay a tenant, so neighbours can vouch for
     * them again if they rejoin. They're emailed the union's records.
     *
     * @throws MembershipRefused
     */
    public function leave(Contact $member): void
    {
        if (! $member->isEnrolled()) {
            throw new MembershipRefused('You\'re not a member of the union.');
        }

        DB::transaction(fn () => $this->end($member));

        $this->sendRecords($member, ExportReason::Left);
    }

    /**
     * Put a removal referendum into effect. Applies even if the member has left
     * while it was being voted on, so they can't be vouched for again. A member
     * is emailed the union's records (one who left already was).
     */
    public function remove(Contact $member): void
    {
        $wasMember = $member->isEnrolled();

        DB::transaction(function () use ($member, $wasMember) {
            if ($wasMember) {
                $this->end($member);
            }

            $member->update(['removed_at' => now()]);
        });

        if ($wasMember) {
            $this->sendRecords($member, ExportReason::Removed);
        }
    }

    /**
     * Delete a tenant's login and contact, ending their membership first. What
     * they wrote stays, shown as by a former member. A member is emailed the
     * union's records.
     */
    public function deleteAccount(User $user): void
    {
        $contact = $user->contact;
        $wasMember = $contact?->isEnrolled();

        DB::transaction(function () use ($user, $contact, $wasMember) {
            if ($wasMember) {
                $this->end($contact);
            }

            DB::table('sessions')->where('user_id', $user->id)->delete();
            $user->delete();
            $contact?->delete();
        });

        if ($wasMember) {
            SendUnionExport::dispatch($contact->building_id, $user->email, ExportReason::AccountDeleted);
        }
    }

    /**
     * Email a former member the union's records, as they were when they stopped being a member.
     */
    private function sendRecords(Contact $member, ExportReason $reason): void
    {
        if ($email = $member->user?->email) {
            SendUnionExport::dispatch($member->building_id, $email, $reason);
        }
    }

    private function end(Contact $member): void
    {
        $member->update(['enrolled_at' => null]);

        // Rejoining means being vouched for again.
        $member->vouchesReceived()->delete();
        $member->roles()->detach();

        // A nomination to a role lapses.
        Referendum::open()
            ->where('kind', ReferendumKind::Elect)
            ->where('candidate_id', $member->id)
            ->update(['closed_at' => now(), 'outcome' => ReferendumOutcome::Withdrawn]);

        $this->giveUpApprovals($member);
    }

    /**
     * Only members approve mail, so a former member's undecided approvals are
     * dropped, and mail that can no longer get enough approvals is rejected.
     */
    private function giveUpApprovals(Contact $member): void
    {
        UnionMail::pending()
            ->whereHas('approvers', fn ($query) => $query->where('contact_id', $member->id)->whereNull('decision'))
            ->get()
            ->each(function (UnionMail $mail) use ($member) {
                $mail->approvers()->where('contact_id', $member->id)->whereNull('decision')->delete();

                if (! $mail->load('approvers')->canStillPass()) {
                    $mail->update(['status' => UnionMailStatus::Rejected, 'decided_at' => now()]);
                }
            });
    }
}
