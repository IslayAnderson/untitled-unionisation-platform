<?php

namespace App\Http\Controllers\Auth;

use App\Actions\RegisterTenant;
use App\Http\Controllers\Controller;
use App\Models\Building;
use App\Services\AddressLookup\AddressLookup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisterController extends Controller
{
    /**
     * New building signup form.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request, AddressLookup $addresses, RegisterTenant $register): RedirectResponse
    {
        $details = $request->validate([
            ...$this->rules(),
            'address_id' => ['required', 'string', 'max:255'],
        ]);

        // Re-fetch the address server-side so a submitted ID can't carry forged details.
        $address = $addresses->find($details['address_id'])
            ?? throw ValidationException::withMessages(['address_id' => 'Choose your building from the address suggestions.']);

        Auth::login($register->forNewBuilding($details, $address));

        return redirect()->route('verification.notice');
    }

    /**
     * Join building signup form, reached from a building's signup URL / QR code.
     */
    public function createJoin(Building $building): View
    {
        return view('auth.join', ['building' => $building]);
    }

    public function storeJoin(Request $request, Building $building, RegisterTenant $register): RedirectResponse
    {
        $details = $request->validate($this->rules());

        Auth::login($register->forExistingBuilding($details, $building));

        return redirect()->route('verification.notice');
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'telephone' => ['nullable', 'string', 'max:32'],
            'unit_name' => ['required', 'string', 'max:100'],
        ];
    }
}
