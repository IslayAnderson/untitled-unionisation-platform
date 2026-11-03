<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Public demo
    |--------------------------------------------------------------------------
    |
    | When enabled, anyone can look around a made-up union at /demo, logged in
    | as one of its members, without changing anything. Build or rebuild it
    | with `php artisan demo:reset` (scheduled daily while enabled).
    |
    */

    'enabled' => (bool) env('DEMO_ENABLED', false),

    /** The demo visitor's login, on a domain that can never receive mail. */
    'email' => 'you@demo.invalid',

];
