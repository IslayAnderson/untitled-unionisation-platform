<?php

/*
 * Error messages in the GOV.UK Design System's style: say what went wrong and how to fix it, in plain
 * words, without "field", "invalid" or a full stop. These are merged over Laravel's own messages.
 *
 * Messages for the fields the forms share are under "custom". A controller passes its own messages where
 * the same field means something else on its form (a policy's title, a poll's question). The general
 * messages at the top are the fallback, for anything not covered.
 *
 * See https://design-system.service.gov.uk/components/error-message/
 */

return [

    'accepted' => 'Select :attribute to confirm',
    'after' => ':Attribute must be after :date',
    'array' => 'Select :attribute',
    'before_or_equal' => ':Attribute must be on or before :date',
    'boolean' => 'Select yes or no for :attribute',
    'confirmed' => 'The :attribute entries do not match',
    'current_password' => 'Your password is not correct',
    'date' => ':Attribute must be a real date',
    'distinct' => 'Each :attribute must be different',
    'email' => 'Enter an email address in the correct format, like name@example.com',
    'enum' => 'Select :attribute',
    'exists' => 'Select :attribute from the list',
    'file' => 'The selected file could not be uploaded – try again',
    'in' => 'Select :attribute from the list',
    'integer' => ':Attribute must be a whole number',
    'lowercase' => ':Attribute must be in lowercase letters',
    'max' => [
        'array' => 'Select no more than :max :attribute',
        'file' => 'The selected file must be smaller than :max kilobytes',
        'numeric' => ':Attribute must be :max or less',
        'string' => ':Attribute must be :max characters or fewer',
    ],
    'mimes' => 'The selected file must be a :values',
    'min' => [
        'array' => 'Select at least :min :attribute',
        'file' => 'The selected file must be at least :min kilobytes',
        'numeric' => ':Attribute must be :min or more',
        'string' => ':Attribute must be :min characters or more',
    ],
    'required' => 'Enter :attribute',
    'string' => 'Enter :attribute as text',
    'unique' => ':Attribute is already in use',

    'custom' => [
        // Signing up and logging in
        'name' => [
            'required' => 'Enter a name',
            'max' => 'Name must be :max characters or fewer',
        ],
        'email' => [
            'required' => 'Enter your email address',
            'email' => 'Enter an email address in the correct format, like name@example.com',
            'lowercase' => 'Email address must be in lowercase letters',
            'max' => 'Email address must be :max characters or fewer',
            'unique' => 'There\'s already an account with this email address – log in instead',
        ],
        'password' => [
            'required' => 'Enter your password',
            'confirmed' => 'The passwords do not match',
            'min' => 'Password must be :min characters or more',
            'current_password' => 'Your password is not correct',
        ],
        'telephone' => [
            'max' => 'Telephone number must be :max characters or fewer',
        ],
        'unit_name' => [
            'required' => 'Enter your flat or room',
            'max' => 'Flat or room must be :max characters or fewer',
        ],
        'address_id' => [
            'required' => 'Enter your building\'s address and select it from the suggestions',
            'max' => 'Enter your building\'s address and select it from the suggestions',
        ],

        // Bulletins, discussions and replies
        'title' => [
            'required' => 'Enter a title',
            'max' => 'Title must be :max characters or fewer',
        ],
        'body' => [
            'required' => 'Enter the text',
            'max' => 'The text must be :max characters or fewer',
        ],
        'reply' => [
            'required' => 'Enter your reply',
            'max' => 'Your reply must be :max characters or fewer',
        ],
        'description' => [
            'max' => 'Description must be :max characters or fewer',
        ],
        'reason' => [
            'required' => 'Enter why',
            'max' => 'Why must be :max characters or fewer',
        ],

        // Votes
        'answer' => [
            'required' => 'Select for, against or abstain',
            'enum' => 'Select for, against or abstain',
        ],
        'contact_id' => [
            'required' => 'Select a member',
            'integer' => 'Select a member',
            'exists' => 'Select a member from the list',
        ],

        // Mail
        'mailing_list_id' => [
            'required' => 'Select a mailing list',
            'integer' => 'Select a mailing list',
            'exists' => 'Select a mailing list from the list',
        ],
        'subject' => [
            'required' => 'Enter a subject',
            'max' => 'Subject must be :max characters or fewer',
        ],
        'decision' => [
            'required' => 'Select approve or reject',
            'enum' => 'Select approve or reject',
        ],

        // Contacts and mailing lists
        'type_id' => [
            'integer' => 'Select a type from the list',
            'exists' => 'Select a type from the list',
        ],
        'landlord_id' => [
            'integer' => 'Select a landlord or agent from the list',
            'exists' => 'Select a landlord or agent from the list',
        ],
        'address' => [
            'max' => 'Address must be :max characters or fewer',
        ],
        'company_number' => [
            'max' => 'Company number must be :max characters or fewer',
        ],
        'contacts.*' => [
            'integer' => 'Select contacts from the list',
            'exists' => 'Select contacts from the list',
        ],
        'contact_email' => [
            'email' => 'Enter an email address in the correct format, like name@example.com',
            'max' => 'Union inbox must be :max characters or fewer',
        ],

        // The constitution's numbers, named by the controller
        'rules.*' => [
            'required' => 'Enter the :attribute',
            'integer' => 'The :attribute must be a whole number',
            'min' => 'The :attribute must be :min or more',
            'max' => 'The :attribute must be :max or less',
        ],

        // Admin
        'confirm_name' => [
            'required' => 'Enter the union\'s name',
            'in' => 'Enter the union\'s name exactly as it\'s shown',
        ],
    ],

    'attributes' => [
        'closes_at' => 'closing date and time',
        'type_id' => 'type',
        'landlord_id' => 'landlord or agent',
        'mailing_list_id' => 'mailing list',
        'contact_id' => 'member',
    ],

];
