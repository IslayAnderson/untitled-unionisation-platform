<?php

namespace App\Http\Controllers;

use App\Http\Requests\FeedbackRequest;
use App\Notifications\FeedbackReceived;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;

/**
 * Feedback about the platform, emailed to config('privacy.email'). The form
 * opens as a popup from the footer; this page is where it falls back to
 * without JavaScript, and where its errors are shown.
 */
class FeedbackController extends Controller
{
    public function create(): View
    {
        return view('feedback.create');
    }

    public function store(FeedbackRequest $request): RedirectResponse
    {
        $feedback = $request->validated();

        Notification::route('mail', config('privacy.email'))->notify(new FeedbackReceived(
            name: $feedback['feedback_name'] ?? null,
            email: $feedback['feedback_email'] ?? null,
            details: $feedback['feedback_details'],
        ));

        return back(fallback: route('home'))->with('status', $feedback['feedback_email'] ?? null
            ? "Thanks for your feedback. We'll reply to {$feedback['feedback_email']} if we need to."
            : 'Thanks for your feedback.');
    }
}
