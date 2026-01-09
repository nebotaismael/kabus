<?php

namespace App\Http\Controllers;

use App\Models\Advertisement;
use App\Models\Orders;
use App\Models\VendorPayment;
use App\Services\NowPaymentsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    protected NowPaymentsService $nowPaymentsService;

    public function __construct(NowPaymentsService $nowPaymentsService)
    {
        $this->nowPaymentsService = $nowPaymentsService;
    }

    /**
     * Handle incoming NowPayments webhook.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function handle(Request $request): JsonResponse
    {
        // Get the signature from header
        $signature = $request->header('x-nowpayments-sig');

        if (empty($signature)) {
            Log::warning('NowPayments webhook: Missing signature header');
            return response()->json(['error' => 'Missing signature'], 403);
        }

        // Get the raw payload
        $payload = $request->all();

        // Validate signature
        if (!$this->nowPaymentsService->validateWebhookSignature($payload, $signature)) {
            // Calculate what the signature should be for debugging
            $calculatedSignature = $this->calculateExpectedSignature($payload);
            
            Log::warning('NowPayments webhook: Invalid signature', [
                'received_signature' => $signature,
                'calculated_signature' => $calculatedSignature,
                'payload' => $payload,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            return response()->json(['error' => 'Invalid signature'], 403);
        }

        // Extract payment information
        $paymentId = $payload['payment_id'] ?? null;
        $paymentStatus = $payload['payment_status'] ?? null;
        $orderId = $payload['order_id'] ?? null;

        Log::info('NowPayments webhook received', [
            'payment_id' => $paymentId,
            'payment_status' => $paymentStatus,
            'order_id' => $orderId,
        ]);

        // Only process finished or confirmed payments
        if (!in_array($paymentStatus, ['finished', 'confirmed'])) {
            Log::info('NowPayments webhook: Payment not yet complete', [
                'payment_id' => $paymentId,
                'status' => $paymentStatus,
            ]);
            return response()->json(['status' => 'ok']);
        }

        // Identify payment type and process accordingly
        if ($this->isOrderPayment($orderId)) {
            $this->processOrderPayment($orderId, $payload);
        } elseif ($this->isVendorFeePayment($orderId)) {
            $this->processVendorFeePayment($orderId, $payload);
        } elseif ($this->isAdvertisementPayment($orderId)) {
            $this->processAdvertisementPayment($orderId, $payload);
        } else {
            Log::warning('NowPayments webhook: Unknown order_id format', [
                'order_id' => $orderId,
            ]);
        }

        return response()->json(['status' => 'ok']);
    }

    /**
     * Check if the order_id corresponds to an order payment.
     *
     * @param string|null $orderId
     * @return bool
     */
    protected function isOrderPayment(?string $orderId): bool
    {
        if (empty($orderId)) {
            return false;
        }

        // Order IDs are UUIDs
        return Orders::where('id', $orderId)->exists();
    }

    /**
     * Check if the order_id corresponds to a vendor fee payment.
     *
     * @param string|null $orderId
     * @return bool
     */
    protected function isVendorFeePayment(?string $orderId): bool
    {
        if (empty($orderId)) {
            return false;
        }

        // Vendor fee payments use the identifier field
        return VendorPayment::where('identifier', $orderId)->exists();
    }

    /**
     * Check if the order_id corresponds to an advertisement payment.
     *
     * @param string|null $orderId
     * @return bool
     */
    protected function isAdvertisementPayment(?string $orderId): bool
    {
        if (empty($orderId)) {
            return false;
        }

        // Advertisement payments use the payment_identifier field
        return Advertisement::where('payment_identifier', $orderId)->exists();
    }

    /**
     * Process an order payment webhook.
     *
     * @param string $orderId
     * @param array $payload
     * @return void
     */
    protected function processOrderPayment(string $orderId, array $payload): void
    {
        $order = Orders::find($orderId);

        if (!$order) {
            Log::warning('NowPayments webhook: Order not found', [
                'order_id' => $orderId,
            ]);
            return;
        }

        // Only update if order is waiting for payment
        if ($order->status !== Orders::STATUS_WAITING_PAYMENT) {
            Log::info('NowPayments webhook: Order already processed', [
                'order_id' => $orderId,
                'current_status' => $order->status,
            ]);
            return;
        }

        // Update order status
        $order->status = Orders::STATUS_PAYMENT_RECEIVED;
        $order->is_paid = true;
        $order->paid_at = now();
        $order->payment_completed_at = now();
        $order->total_received_xmr = $payload['actually_paid'] ?? $payload['pay_amount'] ?? 0;
        $order->save();

        Log::info('NowPayments webhook: Order payment confirmed', [
            'order_id' => $orderId,
            'amount_received' => $order->total_received_xmr,
        ]);
    }

    /**
     * Process a vendor fee payment webhook.
     *
     * @param string $identifier
     * @param array $payload
     * @return void
     */
    protected function processVendorFeePayment(string $identifier, array $payload): void
    {
        $vendorPayment = VendorPayment::where('identifier', $identifier)->first();

        if (!$vendorPayment) {
            Log::warning('NowPayments webhook: Vendor payment not found', [
                'identifier' => $identifier,
            ]);
            return;
        }

        // Only update if payment is not already completed
        if ($vendorPayment->payment_completed) {
            Log::info('NowPayments webhook: Vendor payment already completed', [
                'identifier' => $identifier,
            ]);
            return;
        }

        // Update vendor payment status
        $vendorPayment->payment_completed = true;
        $vendorPayment->total_received = $payload['actually_paid'] ?? $payload['pay_amount'] ?? 0;
        $vendorPayment->save();

        Log::info('NowPayments webhook: Vendor fee payment confirmed', [
            'identifier' => $identifier,
            'amount_received' => $vendorPayment->total_received,
        ]);
    }

    /**
     * Process an advertisement payment webhook.
     *
     * @param string $paymentIdentifier
     * @param array $payload
     * @return void
     */
    protected function processAdvertisementPayment(string $paymentIdentifier, array $payload): void
    {
        $advertisement = Advertisement::where('payment_identifier', $paymentIdentifier)->first();

        if (!$advertisement) {
            Log::warning('NowPayments webhook: Advertisement not found', [
                'payment_identifier' => $paymentIdentifier,
            ]);
            return;
        }

        // Only update if payment is not already completed
        if ($advertisement->payment_completed) {
            Log::info('NowPayments webhook: Advertisement payment already completed', [
                'payment_identifier' => $paymentIdentifier,
            ]);
            return;
        }

        // Update advertisement with payment details
        $advertisement->payment_completed = true;
        $advertisement->payment_completed_at = now();
        $advertisement->starts_at = now();
        $advertisement->ends_at = now()->addDays((int) $advertisement->duration_days);
        $advertisement->total_received = $payload['actually_paid'] ?? $payload['pay_amount'] ?? 0;
        $advertisement->save();

        Log::info('NowPayments webhook: Advertisement payment confirmed', [
            'payment_identifier' => $paymentIdentifier,
            'advertisement_id' => $advertisement->id,
            'amount_received' => $advertisement->total_received,
            'starts_at' => $advertisement->starts_at,
            'ends_at' => $advertisement->ends_at,
        ]);
    }

    /**
     * Calculate the expected signature for debugging purposes.
     *
     * @param array $payload
     * @return string
     */
    protected function calculateExpectedSignature(array $payload): string
    {
        $sortedPayload = $this->sortPayloadRecursively($payload);
        $jsonPayload = json_encode($sortedPayload, JSON_UNESCAPED_SLASHES);
        return hash_hmac('sha512', $jsonPayload, config('nowpayments.ipn_secret'));
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
}
