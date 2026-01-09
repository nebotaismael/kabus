<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NowPaymentsService
{
    protected string $apiKey;
    protected string $ipnSecret;
    protected string $apiUrl;
    protected string $defaultPayCurrency;
    protected string $ipnCallbackUrl;

    public function __construct()
    {
        $this->apiKey = config('nowpayments.api_key');
        $this->ipnSecret = config('nowpayments.ipn_secret');
        $this->apiUrl = config('nowpayments.api_url');
        $this->defaultPayCurrency = config('nowpayments.default_pay_currency', 'xmr');
        $this->ipnCallbackUrl = config('nowpayments.ipn_callback_url');
    }

    /**
     * Create a new payment request with NowPayments.
     *
     * @param float $priceAmount The amount in the price currency (e.g., USD)
     * @param string $priceCurrency The fiat currency code (e.g., 'usd')
     * @param string $payCurrency The cryptocurrency to accept (e.g., 'xmr')
     * @param string|null $orderId Internal order/reference ID
     * @param string|null $case Payment case type ('order' or 'vendor_fee')
     * @return array|null Payment details or null on failure
     */
    public function createPayment(
        float $priceAmount,
        string $priceCurrency,
        string $payCurrency = 'xmr',
        ?string $orderId = null,
        ?string $case = null
    ): ?array {
        $payload = [
            'price_amount' => $priceAmount,
            'price_currency' => strtolower($priceCurrency),
            'pay_currency' => strtolower($payCurrency),
            'ipn_callback_url' => $this->getFullCallbackUrl(),
            'order_id' => $orderId,
            'order_description' => $this->getOrderDescription($case),
        ];

        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->apiUrl . 'payment', $payload);

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'payment_id' => $data['payment_id'] ?? null,
                    'payment_status' => $data['payment_status'] ?? null,
                    'pay_address' => $data['pay_address'] ?? null,
                    'price_amount' => $data['price_amount'] ?? null,
                    'price_currency' => $data['price_currency'] ?? null,
                    'pay_amount' => $data['pay_amount'] ?? null,
                    'pay_currency' => $data['pay_currency'] ?? null,
                    'order_id' => $data['order_id'] ?? null,
                    'created_at' => $data['created_at'] ?? null,
                ];
            }

            Log::error('NowPayments API error', [
                'status' => $response->status(),
                'body' => $response->body(),
                'order_id' => $orderId,
                'case' => $case,
                'price_amount' => $priceAmount,
                'price_currency' => $priceCurrency,
                'pay_currency' => $payCurrency,
                'endpoint' => $this->apiUrl . 'payment',
            ]);

            // Log additional context for specific error types
            if ($response->status() >= 500) {
                Log::warning('NowPayments server error - suggest retry later', [
                    'order_id' => $orderId,
                ]);
            } elseif ($response->status() >= 400) {
                Log::warning('NowPayments client error - check request parameters', [
                    'order_id' => $orderId,
                    'response_body' => $response->json(),
                ]);
            }

            return null;
        } catch (\Exception $e) {
            Log::error('NowPayments API exception', [
                'message' => $e->getMessage(),
                'exception_class' => get_class($e),
                'order_id' => $orderId,
                'case' => $case,
                'trace' => $e->getTraceAsString(),
            ]);

            return null;
        }
    }

    /**
     * Get the status of an existing payment.
     *
     * @param string $paymentId The NowPayments payment ID
     * @return array|null Payment status or null on failure
     */
    public function getPaymentStatus(string $paymentId): ?array
    {
        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
            ])->get($this->apiUrl . 'payment/' . $paymentId);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('NowPayments status check error', [
                'status' => $response->status(),
                'body' => $response->body(),
                'payment_id' => $paymentId,
                'endpoint' => $this->apiUrl . 'payment/' . $paymentId,
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('NowPayments status check exception', [
                'message' => $e->getMessage(),
                'exception_class' => get_class($e),
                'payment_id' => $paymentId,
            ]);

            return null;
        }
    }

    /**
     * Validate webhook signature from NowPayments.
     *
     * @param array $payload The webhook payload
     * @param string $signature The signature from x-nowpayments-sig header
     * @return bool True if signature is valid
     */
    public function validateWebhookSignature(array $payload, string $signature): bool
    {
        $sortedPayload = $this->sortPayloadRecursively($payload);
        $jsonPayload = json_encode($sortedPayload, JSON_UNESCAPED_SLASHES);
        $calculatedSignature = hash_hmac('sha512', $jsonPayload, $this->ipnSecret);

        return hash_equals($calculatedSignature, $signature);
    }

    /**
     * Sort payload keys alphabetically (recursive for nested objects).
     *
     * @param array $payload
     * @return array
     */
    protected function sortPayloadRecursively(array $payload): array
    {
        ksort($payload);

        foreach ($payload as $key => $value) {
            if (is_array($value)) {
                $payload[$key] = $this->sortPayloadRecursively($value);
            }
        }

        return $payload;
    }

    /**
     * Get the full callback URL for IPN notifications.
     *
     * @return string
     */
    protected function getFullCallbackUrl(): string
    {
        $callbackUrl = $this->ipnCallbackUrl;

        // If it's a relative URL, prepend the app URL
        if (str_starts_with($callbackUrl, '/')) {
            $callbackUrl = rtrim(config('app.url'), '/') . $callbackUrl;
        }

        return $callbackUrl;
    }

    /**
     * Get order description based on payment case.
     *
     * @param string|null $case
     * @return string
     */
    protected function getOrderDescription(?string $case): string
    {
        return match ($case) {
            'vendor_fee' => 'Vendor registration fee',
            'order' => 'Order payment',
            'advertisement' => 'Advertisement payment',
            default => 'Payment',
        };
    }

    /**
     * Create a payout (withdrawal) to send cryptocurrency to an external address.
     *
     * @param string $address The recipient's cryptocurrency address
     * @param float $amount The amount to send in cryptocurrency
     * @param string $currency The cryptocurrency code (e.g., 'xmr')
     * @param string|null $description Optional description for the payout
     * @return array|null Payout details or null on failure
     */
    public function createPayout(
        string $address,
        float $amount,
        string $currency = 'xmr',
        ?string $description = null
    ): ?array {
        // Check if payouts are enabled
        if (!config('nowpayments.payout_enabled', true)) {
            Log::warning('NowPayments payout attempted but payouts are disabled', [
                'address' => $address,
                'amount' => $amount,
                'currency' => $currency,
            ]);
            return null;
        }

        $payload = [
            'address' => $address,
            'amount' => $amount,
            'currency' => strtolower($currency),
            'ipn_callback_url' => $this->getPayoutCallbackUrl(),
        ];

        if ($description) {
            $payload['extraId'] = $description;
        }

        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->apiUrl . 'payout', $payload);

            if ($response->successful()) {
                $data = $response->json();

                Log::info('NowPayments payout created successfully', [
                    'payout_id' => $data['id'] ?? null,
                    'status' => $data['status'] ?? null,
                    'address' => $address,
                    'amount' => $amount,
                    'currency' => $currency,
                ]);

                return [
                    'id' => $data['id'] ?? null,
                    'status' => $data['status'] ?? null,
                    'amount' => $data['amount'] ?? $amount,
                    'currency' => $data['currency'] ?? $currency,
                    'address' => $data['address'] ?? $address,
                    'hash' => $data['hash'] ?? null,
                    'created_at' => $data['created_at'] ?? null,
                ];
            }

            Log::error('NowPayments payout API error', [
                'status' => $response->status(),
                'body' => $response->body(),
                'address' => $address,
                'amount' => $amount,
                'currency' => $currency,
                'description' => $description,
                'endpoint' => $this->apiUrl . 'payout',
            ]);

            // Log additional context for specific error types
            if ($response->status() >= 500) {
                Log::warning('NowPayments payout server error - suggest retry later', [
                    'address' => $address,
                    'amount' => $amount,
                ]);
            } elseif ($response->status() >= 400) {
                $responseData = $response->json();
                Log::warning('NowPayments payout client error - check request parameters', [
                    'address' => $address,
                    'amount' => $amount,
                    'response_body' => $responseData,
                ]);

                // Check for insufficient balance
                if (isset($responseData['message']) && stripos($responseData['message'], 'balance') !== false) {
                    Log::critical('NowPayments payout failed - possible insufficient balance', [
                        'address' => $address,
                        'amount' => $amount,
                    ]);
                }
            }

            return null;
        } catch (\Exception $e) {
            Log::error('NowPayments payout API exception', [
                'message' => $e->getMessage(),
                'exception_class' => get_class($e),
                'address' => $address,
                'amount' => $amount,
                'currency' => $currency,
                'description' => $description,
                'trace' => $e->getTraceAsString(),
            ]);

            return null;
        }
    }

    /**
     * Get the full callback URL for payout IPN notifications.
     *
     * @return string
     */
    protected function getPayoutCallbackUrl(): string
    {
        $callbackUrl = config('nowpayments.payout_callback_url', '/api/webhooks/nowpayments/payout');

        // If it's a relative URL, prepend the app URL
        if (str_starts_with($callbackUrl, '/')) {
            $callbackUrl = rtrim(config('app.url'), '/') . $callbackUrl;
        }

        return $callbackUrl;
    }
}
