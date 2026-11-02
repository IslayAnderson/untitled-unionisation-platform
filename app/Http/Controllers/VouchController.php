<?php

namespace App\Http\Controllers;

use App\Exceptions\VouchRefused;
use App\Models\Contact;
use App\Services\Vouching;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The page a tenant's personal QR code opens on another tenant's phone.
 */
class VouchController extends Controller
{
    public function show(Request $request, Contact $contact, Vouching $vouching): View
    {
        return view('vouch', [
            'vouchee' => $contact,
            'refusal' => $vouching->refusal($request->user()->contact, $contact),
        ]);
    }

    public function store(Request $request, Contact $contact, Vouching $vouching): RedirectResponse
    {
        try {
            $vouching->vouch($request->user()->contact, $contact);
        } catch (VouchRefused $e) {
            return back()->withErrors(['vouch' => $e->getMessage()]);
        }

        return redirect()->route('dashboard')->with('status', "You vouched for {$contact->name}.");
    }
}
