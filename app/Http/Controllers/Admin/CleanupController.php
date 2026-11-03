<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\DeleteUnion;
use App\Models\Building;
use App\Services\Cleanup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Each tool works in two steps: warn the account holders by email, then,
 * GRACE_DAYS later, delete those who haven't logged in since.
 */
class CleanupController extends Controller
{
    public const ACCOUNT_TOOLS = ['unverified', 'unplaced', 'unvouched'];

    public function index(Cleanup $cleanup): View
    {
        $descriptions = [
            'unverified' => ['Unconfirmed accounts', 'Sign-ups that never confirmed their email address within '.Cleanup::UNVERIFIED_DAYS.' days.'],
            'unplaced' => ['Unfinished sign-ups', 'Confirmed accounts that never chose their building and flat, '.Cleanup::UNPLACED_DAYS.' or more days after signing up.'],
            'unvouched' => ['Never vouched for', 'Tenants of unions with members who still haven\'t been vouched for, '.Cleanup::UNVOUCHED_DAYS.' or more days after signing up. They\'d need to sign up again.'],
        ];

        $tools = collect($descriptions)->map(function (array $text, string $tool) use ($cleanup) {
            $accounts = $cleanup->accounts($tool);

            return [
                'title' => $text[0],
                'description' => $text[1],
                'to_warn' => $accounts->clone()->whereNull('cleanup_warned_at')->count(),
                'waiting' => $cleanup->awaitingDeadline($accounts)->count(),
                'next_deadline' => $cleanup->awaitingDeadline($accounts)->min('cleanup_warned_at'),
                'due' => $cleanup->dueForDeletion($accounts)->count(),
            ];
        });

        return view('admin.cleanup', [
            'tools' => $tools,
            'abandoned' => $cleanup->abandonedUnions()->withCount('contacts')->orderBy('created_at')->get(),
            'abandonedDue' => $cleanup->unionsDueForDeletion($cleanup->abandonedUnions())->pluck('id'),
        ]);
    }

    public function warnAccounts(Request $request, string $tool, Cleanup $cleanup): RedirectResponse
    {
        $warned = $cleanup->warnAccounts($cleanup->accounts($tool), $tool, $request->user());

        return back()->with('status', "Emailed {$warned} ".str('account holder')->plural($warned).'. Anyone who doesn\'t log in within '.Cleanup::GRACE_DAYS.' days can then be deleted.');
    }

    public function destroyAccounts(Request $request, string $tool, Cleanup $cleanup): RedirectResponse
    {
        $deleted = $cleanup->deleteAccounts($cleanup->dueForDeletion($cleanup->accounts($tool)), $request->user(), $tool);

        return back()->with('status', "Deleted {$deleted} ".str('account')->plural($deleted).'.');
    }

    public function warnAbandoned(Request $request, Cleanup $cleanup): RedirectResponse
    {
        $unions = $cleanup->abandonedUnions()->whereNull('cleanup_warned_at')->get();
        $unions->each(fn (Building $union) => $cleanup->warnUnion(
            $union,
            "{$union->name} has no members, and nobody has signed up to it for ".Cleanup::ABANDONED_DAYS.' days.',
            $request->user(),
        ));

        return back()->with('status', "Emailed everyone in {$unions->count()} abandoned ".str('union')->plural($unions->count()).'.');
    }

    public function destroyAbandoned(Request $request, Cleanup $cleanup): RedirectResponse
    {
        $unions = $cleanup->unionsDueForDeletion($cleanup->abandonedUnions())->get();
        $unions->each(fn (Building $union) => DeleteUnion::dispatch($union->id, $request->user()->id));

        return back()->with('status', "Deleting {$unions->count()} abandoned ".str('union')->plural($unions->count()).' in the background.');
    }
}
