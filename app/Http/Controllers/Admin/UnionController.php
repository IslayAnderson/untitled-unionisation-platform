<?php

namespace App\Http\Controllers\Admin;

use App\Enums\QrScanKind;
use App\Enums\UnionMailStatus;
use App\Http\Controllers\Controller;
use App\Jobs\DeleteUnion;
use App\Models\Building;
use App\Models\QrScan;
use App\Models\User;
use App\Services\Cleanup;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Usage numbers for each union. Admins see counts, never members' names or
 * what anyone wrote: see docs/architecture/admin.md.
 */
class UnionController extends Controller
{
    public function index(): View
    {
        $unions = $this->withStats(Building::query())->latest()->get();

        // The demo is listed, but left out of the totals: its visitors aren't real usage.
        $real = $unions->reject(fn (Building $union) => $union->is_demo);
        $active = $real->filter(fn (Building $union) => $union->last_active_at?->gt(now()->subDays(30)))->count();

        return view('admin.unions.index', [
            'unions' => $unions,
            'totals' => [
                'Unions' => $real->count(),
                'Members' => $real->sum('members_count'),
                'Waiting to be vouched for' => $real->sum('waiting_count'),
                'Accounts' => User::whereDoesntHave('contact.building', fn (Builder $building) => $building->where('is_demo', true))->count(),
                'Active unions (last 30 days)' => $active,
                'Inactive unions (nothing new in 30 days)' => $real->count() - $active,
            ],
            'qrScans' => $this->qrScansByKind(),
        ]);
    }

    public function show(int $building, Cleanup $cleanup): View
    {
        $union = $this->withStats(Building::whereKey($building))->firstOrFail();

        return view('admin.unions.show', [
            'union' => $union,
            'deadline' => $union->cleanup_warned_at ? $cleanup->deadline($union->cleanup_warned_at) : null,
            'due' => $cleanup->unionsDueForDeletion(Building::whereKey($union->id))->exists(),
        ]);
    }

    /**
     * Email everyone in the union that it will be deleted unless one of them
     * logs in within GRACE_DAYS.
     */
    public function warn(Request $request, Building $building, Cleanup $cleanup): RedirectResponse
    {
        abort_if($building->is_demo, 409, 'The demo union is managed with php artisan demo:reset.');
        $this->confirmName($request, $building);

        $cleanup->warnUnion($building, "The platform's administrators are closing {$building->name}.", $request->user());

        return back()->with('status', "Emailed everyone in {$building->name}. If nobody logs in within ".Cleanup::GRACE_DAYS.' days, you can delete it.');
    }

    public function cancelWarning(Building $building, Cleanup $cleanup): RedirectResponse
    {
        $cleanup->cancelUnionWarning($building);

        return back()->with('status', "{$building->name} won't be deleted. Its members haven't been told.");
    }

    public function destroy(Request $request, Building $building, Cleanup $cleanup): RedirectResponse
    {
        $this->confirmName($request, $building);

        abort_unless($cleanup->unionsDueForDeletion(Building::whereKey($building->id))->exists(), 409, 'This union can only be deleted '.Cleanup::GRACE_DAYS.' days after its members were warned, if none of them has logged in since.');

        DeleteUnion::dispatch($building->id, $request->user()->id);

        return redirect()->route('admin.unions.index')
            ->with('status', "Deleting {$building->name} in the background. Its members will be emailed its records first.");
    }

    /**
     * How many times each kind of QR code has been scanned, on the whole platform except the demo.
     *
     * @return array<string, int> Label => scans
     */
    private function qrScansByKind(): array
    {
        $counts = QrScan::whereDoesntHave('building', fn (Builder $building) => $building->where('is_demo', true))
            ->toBase()->selectRaw('kind, count(*) as scans')->groupBy('kind')->pluck('scans', 'kind');

        return collect(QrScanKind::cases())
            ->mapWithKeys(fn (QrScanKind $kind) => [$kind->label() => (int) ($counts[$kind->value] ?? 0)])
            ->all();
    }

    private function confirmName(Request $request, Building $building): void
    {
        $request->validate(['confirm_name' => ['required', 'in:'.$building->name]]);
    }

    /**
     * @param  Builder<Building>  $query
     * @return Builder<Building>
     */
    private function withStats(Builder $query): Builder
    {
        return $query
            ->withCount([
                'contacts as members_count' => fn (Builder $contact) => $contact->whereNotNull('enrolled_at'),
                'contacts as waiting_count' => fn (Builder $contact) => $contact->confirmedTenants()->whereNull('enrolled_at')->whereNull('removed_at'),
                'contacts as unconfirmed_count' => fn (Builder $contact) => $contact->whereHas('user', fn (Builder $user) => $user->whereNull('email_verified_at')),
                'bulletins' => fn (Builder $bulletin) => $bulletin->whereNull('system_key'),
                'threads',
                'polls',
                'unionMails as mail_sent_count' => fn (Builder $mail) => $mail->where('status', UnionMailStatus::Sent),
                'unionMails as mail_pending_count' => fn (Builder $mail) => $mail->pending(),
                'referendums',
                'referendums as open_referendums_count' => fn (Builder $referendum) => $referendum->undecided(),
                'policies',
                'roles',
                'landlords',
                'externalContacts',
                'qrScans',
                'qrScans as join_scans_count' => fn (Builder $scan) => $scan->where('kind', QrScanKind::Join),
                'qrScans as vouch_scans_count' => fn (Builder $scan) => $scan->where('kind', QrScanKind::Vouch),
            ])
            ->withMax('bulletins', 'created_at')
            ->withMax('threads', 'created_at')
            ->withMax('unionMails', 'created_at')
            ->withMax('referendums', 'created_at')
            ->withMax(['contacts as last_signup_at' => fn (Builder $contact) => $contact->whereHas('user')], 'created_at')
            ->withCasts([
                'bulletins_max_created_at' => 'datetime',
                'threads_max_created_at' => 'datetime',
                'union_mails_max_created_at' => 'datetime',
                'referendums_max_created_at' => 'datetime',
                'last_signup_at' => 'datetime',
            ])
            ->tap(fn (Builder $query) => $query->afterQuery(fn (Collection $unions) => $unions->each(
                fn (Building $union) => $union->last_active_at = collect([
                    $union->bulletins_max_created_at,
                    $union->threads_max_created_at,
                    $union->union_mails_max_created_at,
                    $union->referendums_max_created_at,
                    $union->last_signup_at,
                ])->filter()->max(),
            )));
    }
}
