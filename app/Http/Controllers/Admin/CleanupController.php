<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Building;
use App\Services\Cleanup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CleanupController extends Controller
{
    public const ACCOUNT_TOOLS = ['unverified', 'unplaced', 'unvouched'];

    public function index(Cleanup $cleanup): View
    {
        return view('admin.cleanup', [
            'tools' => [
                'unverified' => [
                    'title' => 'Unconfirmed accounts',
                    'description' => 'Sign-ups that never confirmed their email address within '.Cleanup::UNVERIFIED_DAYS.' days.',
                    'count' => $cleanup->unverifiedAccounts()->count(),
                ],
                'unplaced' => [
                    'title' => 'Unfinished sign-ups',
                    'description' => 'Confirmed accounts that never chose their building and flat, '.Cleanup::UNPLACED_DAYS.' or more days after signing up.',
                    'count' => $cleanup->unplacedAccounts()->count(),
                ],
                'unvouched' => [
                    'title' => 'Never vouched for',
                    'description' => 'Tenants of unions with members who still haven\'t been vouched for, '.Cleanup::UNVOUCHED_DAYS.' or more days after signing up. They\'d need to sign up again.',
                    'count' => $cleanup->unvouchedAccounts()->count(),
                ],
            ],
            'abandoned' => $cleanup->abandonedUnions()->withCount('contacts')->orderBy('created_at')->get(),
        ]);
    }

    public function destroyAccounts(Request $request, string $tool, Cleanup $cleanup): RedirectResponse
    {
        $query = match ($tool) {
            'unverified' => $cleanup->unverifiedAccounts(),
            'unplaced' => $cleanup->unplacedAccounts(),
            'unvouched' => $cleanup->unvouchedAccounts(),
        };

        $deleted = $cleanup->deleteAccounts($query, $request->user(), $tool);

        return back()->with('status', "Deleted {$deleted} ".str('account')->plural($deleted).'.');
    }

    public function destroyAbandoned(Request $request, Cleanup $cleanup): RedirectResponse
    {
        $unions = $cleanup->abandonedUnions()->get();
        $unions->each(fn (Building $union) => $cleanup->deleteUnion($union, $request->user()));

        return back()->with('status', "Deleted {$unions->count()} abandoned ".str('union')->plural($unions->count()).'.');
    }
}
