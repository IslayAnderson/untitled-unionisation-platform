<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Data Controller
    |--------------------------------------------------------------------------
    |
    | Who is responsible for personal data on the platform under UK GDPR, as
    | named in the privacy policy, and where people write to about it. Feedback
    | sent from the site is emailed to this address too.
    |
    */

    'controller' => env('PRIVACY_CONTROLLER', 'Islay Anderson'),

    'email' => env('PRIVACY_EMAIL', env('MAIL_FROM_ADDRESS')),

    // The ICO registration number, if the controller is registered.
    'ico_registration' => env('PRIVACY_ICO_REGISTRATION'),

];
