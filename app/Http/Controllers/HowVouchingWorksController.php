<?php

namespace App\Http\Controllers;

use App\Support\ConstitutionRules;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Explains vouching with diagrams. Tenants see their own union's numbers;
 * everyone else sees the default constitution's.
 */
class HowVouchingWorksController extends Controller
{
    public function __invoke(Request $request): View
    {
        $building = $request->user()?->contact?->building;

        return view('vouching-guide', [
            'rules' => $building?->rules() ?? ConstitutionRules::fromArray([]),
            'building' => $building,
        ]);
    }
}
