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
    | HTTP Client Configuration
    |--------------------------------------------------------------------------
    |
    | Timeout and retry settings for API requests. Increase these values if
    | you experience connection timeouts in high-latency environments.
    |
    */

    'timeout' => env('NOWPAYMENTS_TIMEOUT', 60),
    'connect_timeout' => env('NOWPAYMENTS_CONNECT_TIMEOUT', 30),
    'max_retries' => env('NOWPAYMENTS_MAX_RETRIES', 3),

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
    | The Payout API uses JWT authentication - you must provide your NowPayments
    | account email and password to authenticate payout requests.
    |
    */

    'payout_enabled' => env('NOWPAYMENTS_PAYOUT_ENABLED', true),

    'payout_callback_url' => env('NOWPAYMENTS_PAYOUT_CALLBACK_URL', '/api/webhooks/nowpayments/payout'),

    // NowPayments account credentials for Payout API JWT authentication
    'payout_email' => env('NOWPAYMENTS_PAYOUT_EMAIL'),
    'payout_password' => env('NOWPAYMENTS_PAYOUT_PASSWORD'),

    /*
    |--------------------------------------------------------------------------
    | Supported Cryptocurrencies
    |--------------------------------------------------------------------------
    |
    | List of cryptocurrencies available for payments. Each currency includes
    | its display name, symbol, decimal precision, and URI scheme for QR codes.
    | Set 'enabled' to false to disable a currency without removing it.
    |
    */

    'supported_currencies' => [
        'xmr' => [
            'name' => 'Monero',
            'symbol' => 'XMR',
            'decimals' => 12,
            'uri_scheme' => 'monero',
            'enabled' => true,
        ],
        'btc' => [
            'name' => 'Bitcoin',
            'symbol' => 'BTC',
            'decimals' => 8,
            'uri_scheme' => 'bitcoin',
            'enabled' => true,
        ],
        'ltc' => [
            'name' => 'Litecoin',
            'symbol' => 'LTC',
            'decimals' => 8,
            'uri_scheme' => 'litecoin',
            'enabled' => true,
        ],
        'eth' => [
            'name' => 'Ethereum',
            'symbol' => 'ETH',
            'decimals' => 18,
            'uri_scheme' => 'ethereum',
            'enabled' => true,
        ],
        'usdttrc20' => [
            'name' => 'Tether (TRC20)',
            'symbol' => 'USDT',
            'decimals' => 6,
            'uri_scheme' => null,
            'enabled' => true,
        ],
    ],
];
