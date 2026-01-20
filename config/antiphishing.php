<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Anti-Phishing Feature Toggle
    |--------------------------------------------------------------------------
    |
    | Enable or disable the anti-phishing address verification challenge.
    | When enabled, users must verify they know the official .onion address
    | before accessing the login form.
    |
    */
    'enabled' => env('ANTIPHISHING_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Official Onion Addresses
    |--------------------------------------------------------------------------
    |
    | Array of official .onion addresses of the marketplace (without http://).
    | Users can access the site through any of these addresses.
    | The system will detect which address the user is accessing and use that
    | for the verification challenge.
    | Each must be a valid v3 onion address (56 characters + .onion).
    |
    */
    'onion_addresses' => [
        env('ANTIPHISHING_ONION_ADDRESS_1', 'hecatemr6nlifty5nr47xooda4mofkrrqenvaa7hmdt5qlfyahnzv5ad.onion'),
        env('ANTIPHISHING_ONION_ADDRESS_2', 'hecatejwtxexu2gz3o7pgd765aisnsijmtwjnropqe62j2obfzlfqtqd.onion'),
        env('ANTIPHISHING_ONION_ADDRESS_3', 'hecate5lug6m73yhwg5v4mj3hinxj5qwqibfwjwkkqs45z6nkq4gyyid.onion'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Legacy Single Address (Deprecated)
    |--------------------------------------------------------------------------
    |
    | For backwards compatibility. Use onion_addresses array instead.
    |
    */
    'onion_address' => env('ANTIPHISHING_ONION_ADDRESS', ''),

    /*
    |--------------------------------------------------------------------------
    | Challenge Difficulty
    |--------------------------------------------------------------------------
    |
    | Number of characters to mask in the address challenge (2-8).
    | Higher values make the challenge more difficult but also more secure.
    |
    */
    'difficulty' => env('ANTIPHISHING_DIFFICULTY', 4),

    /*
    |--------------------------------------------------------------------------
    | Challenge Time Limit
    |--------------------------------------------------------------------------
    |
    | Time limit for completing the challenge in minutes (1-10).
    | After this time, the challenge expires and a new one must be generated.
    |
    */
    'time_limit' => env('ANTIPHISHING_TIME_LIMIT', 5),

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting - Maximum Attempts
    |--------------------------------------------------------------------------
    |
    | Maximum number of failed challenge attempts allowed before lockout.
    | This helps prevent brute-force attacks on the verification system.
    |
    */
    'max_attempts' => env('ANTIPHISHING_MAX_ATTEMPTS', 5),

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting - Lockout Duration
    |--------------------------------------------------------------------------
    |
    | Duration in minutes that a user is locked out after exceeding
    | the maximum number of failed attempts.
    |
    */
    'lockout_minutes' => env('ANTIPHISHING_LOCKOUT_MINUTES', 10),
];
