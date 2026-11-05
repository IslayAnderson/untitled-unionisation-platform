<?php

namespace App\Http\Controllers;

use App\Models\DemoSession;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * The public demo: anyone can look around a made-up union as one of its
 * members. See docs/architecture/demo.md.
 */
class DemoController extends Controller
{
    public function show(): View
    {
        abort_unless(config('demo.enabled'), 404);

        return view('demo', ['user' => $this->demoUser()]);
    }

    public function enter(Request $request): RedirectResponse
    {
        abort_unless(config('demo.enabled') && $user = $this->demoUser(), 404);

        Auth::login($user);
        $request->session()->regenerate();
        DemoSession::create();

        return redirect()->route('dashboard');
    }

    private function demoUser(): ?User
    {
        return User::where('email', config('demo.email'))
            ->whereHas('contact.building', fn ($building) => $building->where('is_demo', true))
            ->first();
    }
}
