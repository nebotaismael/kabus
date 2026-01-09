<?php

return [
    /*
    |--------------------------------------------------------------------------
    | NowPayments API Configuration
    |--------------------------------------------------------------------------
    |
    | These settings define the connection parameters for the NowPayments.io
    | payment gateway API. Configure your API key and IPN secret from the
    | NowPayments dashboard.
    |
    */

    'api_key' => env('NOWPAYMENTS_API_KEY'),
    'ipn_secret' => env('NOWPAYMENTS_IPN_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | API URL Configuration
    |--------------------------------------------------------------------------
    |
    | The API URL is determined by the environment setting. Use 'sandbox' for
    | testing and 'live' for production payments.
    |
    */

    'api_url' => env('NOWPAYMENTS_ENV') === 'sandbox'
        ? 'https://api-sandbox.nowpayments.io/v1/'
        : 'https://api.nowpayments.io/v1/',

    /*
    |--------------------------------------------------------------------------
    | Default Payment Currency
    |--------------------------------------------------------------------------
    |
    | The default cryptocurrency to accept for payments. This can be overridden
    | per payment request if needed.
    |
    */

    'default_pay_currency' => env('NOWPAYMENTS_DEFAULT_CURRENCY', 'xmr'),

    /*
    |--------------------------------------------------------------------------
    | Webhook Configuration
    |--------------------------------------------------------------------------
    |
    | The IPN callback URL where NowPayments will send payment notifications.
    | This should be the full URL to your webhook endpoint.
    |
    */

    'ipn_callback_url' => env('NOWPAYMENTS_IPN_CALLBACK_URL', '/api/webhooks/nowpayments'),

    /*
    |--------------------------------------------------------------------------
    | Payout Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for NowPayments Payout API. Payouts are used to send
    | cryptocurrency to external addresses (vendor payments, refunds, etc.).
    | Note: Payout API requires a verified business account with sufficient balance.
    |
    */

    'payout_enabled' => env('NOWPAYMENTS_PAYOUT_ENABLED', true),

    'payout_callback_url' => env('NOWPAYMENTS_PAYOUT_CALLBACK_URL', '/api/webhooks/nowpayments/payout'),
];
