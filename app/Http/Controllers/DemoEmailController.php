<?php

namespace App\Http\Controllers;

use App\Models\Building;
use App\Support\SampleEmail;
use App\Support\SampleEmails;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * Every email the platform sends, as the demo union's would look, for anyone
 * to read without entering the demo. Nothing is sent. See docs/architecture/demo.md.
 */
class DemoEmailController extends Controller
{
    /** Where the demo's emails are addressed. It's never sent to. */
    public const ADDRESS = 'you@example.com';

    public function index(): View
    {
        return view('demo-emails.index', ['emails' => $this->emails()]);
    }

    public function show(string $sample): View
    {
        $emails = $this->emails();
        abort_unless(isset($emails[$sample]), 404);

        $slugs = array_keys($emails);
        $position = array_search($sample, $slugs, true);

        return view('demo-emails.show', [
            'email' => $emails[$sample],
            'previous' => $emails[$slugs[$position - 1] ?? null] ?? null,
            'next' => $emails[$slugs[$position + 1] ?? null] ?? null,
        ]);
    }

    /**
     * The email itself, for the show page's frame. Links open in a new tab, out of the frame.
     */
    public function html(string $sample): Response
    {
        $email = $this->emails()[$sample] ?? abort(404);

        return response(preg_replace('/<head>/i', '<head><base target="_blank">', $email->html(), 1))
            ->header('Content-Security-Policy', "script-src 'none'; frame-ancestors 'self'");
    }

    /**
     * @return array<string, SampleEmail>
     */
    private function emails(): array
    {
        abort_unless(config('demo.enabled'), 404);

        return SampleEmails::for(Building::where('is_demo', true)->firstOrFail(), self::ADDRESS);
    }
}
