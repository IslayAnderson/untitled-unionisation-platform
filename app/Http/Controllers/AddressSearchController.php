<?php

namespace App\Http\Controllers;

use App\Services\AddressLookup\AddressLookup;
use App\Services\AddressLookup\AddressResult;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AddressSearchController extends Controller
{
    public function __invoke(Request $request, AddressLookup $addresses): JsonResponse
    {
        $query = $request->validate(['q' => ['required', 'string', 'min:3', 'max:200']])['q'];

        return response()->json(
            $addresses->search($query)->map(fn (AddressResult $address) => [
                'id' => $address->providerId,
                'label' => $address->formatted,
            ]),
        );
    }
}
