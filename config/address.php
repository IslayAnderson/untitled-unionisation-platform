<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Address Lookup Driver
    |--------------------------------------------------------------------------
    |
    | The provider used to search UK addresses when registering a building.
    | Supported: "geoapify", "fake"
    |
    */

    'driver' => env('ADDRESS_LOOKUP_DRIVER', 'geoapify'),

    'drivers' => [
        'geoapify' => [
            'key' => env('GEOAPIFY_API_KEY'),
        ],
    ],

];
