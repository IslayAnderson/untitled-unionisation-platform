<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UnionMailStatus;
use App\Http\Controllers\Controller;
use App\Models\Building;
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

        return view('admin.unions.index', [
            'unions' => $unions,
            'totals' => [
                'Unions' => $unions->count(),
                'Members' => $unions->sum('members_count'),
                'Waiting to be vouched for' => $unions->sum('waiting_count'),
                'Accounts' => User::count(),
                'Active unions (last 30 days)' => $unions->filter(fn (Building $union) => $union->last_active_at?->gt(now()->subDays(30)))->count(),
            ],
        ]);
    }

    public function show(int $building): View
    {
        $union = $this->withStats(Building::whereKey($building))->firstOrFail();

        return view('admin.unions.show', ['union' => $union]);
    }

    public function destroy(Request $request, Building $building, Cleanup $cleanup): RedirectResponse
    {
        $request->validate(['confirm_name' => ['required', 'in:'.$building->name]], [
            'confirm_name.in' => "Type the union's name exactly to delete it.",
        ]);

        $cleanup->deleteUnion($building, $request->user());

        return redirect()->route('admin.unions.index')->with('status', "Deleted {$building->name} and everything in it.");
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
                'contacts as waiting_count' => fn (Builder $contact) => $contact->confirmedTenants()->whereNull('enrolled_at'),
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
