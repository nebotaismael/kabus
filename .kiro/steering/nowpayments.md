---
inclusion: fileMatch
fileMatchPattern: "**/NowPayments*.php,**/Webhook*.php,**/nowpayments.php,**/Orders.php,**/VendorController.php,**/AdminController.php,**/Advertisement.php"
---

# NowPayments Integration Guidelines

## Overview

This project uses NowPayments.io as the cryptocurrency payment gateway for ALL payment operations:

- **Incoming Payments:** Order payments, vendor fees, advertisement payments
- **Outgoing Payments (Payouts):** Vendor payouts, buyer refunds, application refunds

**IMPORTANT:** Do NOT use Monero RPC (`walletRPC`) for any payment operations. All payments must go through NowPaymentsService.

## API Reference

### Base URLs
- **Sandbox:** `https://api-sandbox.nowpayments.io/v1/`
- **Production:** `https://api.nowpayments.io/v1/`

### Key Endpoints

#### Incoming Payments
- `POST /payment` - Create a new payment request
- `GET /payment/{payment_id}` - Get payment status
- `GET /status` - API status check

#### Outgoing Payments (Payouts)
- `POST /payout` - Create a payout (withdrawal)
- `GET /payout/{payout_id}` - Get payout status

### Required Headers
```
x-api-key: {your_api_key}
Content-Type: application/json
```

## Payment Statuses

NowPayments uses these payment statuses:

| Status | Description |
|--------|-------------|
| `waiting` | Payment created, awaiting deposit |
| `confirming` | Deposit detected, awaiting confirmations |
| `confirmed` | Payment confirmed on blockchain |
| `sending` | Sending funds to merchant wallet |
| `partially_paid` | Partial payment received |
| `finished` | Payment complete |
| `failed` | Payment failed |
| `expired` | No payment received within 7 days |

**Important:** Treat both `finished` and `confirmed` as successful payment statuses.

## Webhook (IPN) Handling

### Signature Validation

Always validate webhook signatures using this algorithm:

```php
function validateSignature(array $payload, string $receivedSig, string $secret): bool
{
    // 1. Sort keys recursively
    $this->sortKeysRecursive($payload);
    
    // 2. JSON encode with unescaped slashes
    $json = json_encode($payload, JSON_UNESCAPED_SLASHES);
    
    // 3. Calculate HMAC-SHA512
    $calculatedSig = hash_hmac('sha512', $json, $secret);
    
    // 4. Compare signatures
    return hash_equals($calculatedSig, $receivedSig);
}

function sortKeysRecursive(array &$array): void
{
    ksort($array);
    foreach ($array as &$value) {
        if (is_array($value)) {
            $this->sortKeysRecursive($value);
        }
    }
}
```

### Webhook IP Whitelist

NowPayments sends webhooks from these IPs (whitelist in firewall):
- 51.89.194.21
- 51.75.77.69
- 138.201.172.58
- 65.21.158.36

## Configuration

Environment variables required:

```env
NOWPAYMENTS_API_KEY=your_api_key
NOWPAYMENTS_IPN_SECRET=your_ipn_secret
NOWPAYMENTS_ENV=sandbox  # or 'live' for production

# Payout Configuration (requires verified business account)
NOWPAYMENTS_PAYOUT_ENABLED=true
```

### Payout API Requirements

To use the Payout API for vendor payments and refunds:
1. Your NowPayments account must be a verified business account
2. The API key must have payout permissions enabled
3. Your account must have sufficient balance to cover payouts

## Code Patterns

### Creating a Payment

```php
$payment = $nowPaymentsService->createPayment(
    priceAmount: $order->total,      // Amount in fiat
    priceCurrency: 'usd',            // Fiat currency
    payCurrency: 'xmr',              // Crypto to accept
    orderId: $order->id,             // Your internal ID
    case: 'order'                    // Payment type identifier
);

if ($payment) {
    $order->update([
        'np_payment_id' => $payment['payment_id'],
        'pay_address' => $payment['pay_address'],
        'pay_amount' => $payment['pay_amount'],
    ]);
}
```

### Processing Webhooks

```php
// In WebhookController::handle()
$status = $request->input('payment_status');

if (in_array($status, ['finished', 'confirmed'])) {
    // Payment successful - update your records
    $order = Orders::where('np_payment_id', $request->input('payment_id'))->first();
    if ($order && !$order->is_paid) {
        $order->update([
            'status' => Orders::STATUS_PAYMENT_RECEIVED,
            'is_paid' => true,
            'paid_at' => now(),
        ]);
    }
}
```

## Testing

### Sandbox Testing
1. Use sandbox API URL and sandbox API key
2. NowPayments sandbox allows testing without real crypto
3. Webhooks work in sandbox mode

### Mocking in Tests
```php
Http::fake([
    'api-sandbox.nowpayments.io/*' => Http::response([
        'payment_id' => '123456789',
        'pay_address' => 'test_address',
        'pay_amount' => 0.5,
        'payment_status' => 'waiting',
    ], 200),
]);
```

## Common Issues

1. **Webhook not received:** Check firewall allows NowPayments IPs
2. **Invalid signature:** Ensure recursive key sorting and `JSON_UNESCAPED_SLASHES`
3. **CSRF errors:** Webhook route must be excluded from CSRF middleware
4. **Payment expired:** Payments expire after 7 days with no deposit
5. **Payout failed:** Check account balance and payout permissions on API key

## Payout Operations

### Creating a Payout (Vendor Payment / Refund)

```php
$payout = $nowPaymentsService->createPayout(
    address: $vendorAddress,         // Recipient XMR address
    amount: $payoutAmount,           // Amount in crypto
    currency: 'xmr',                 // Cryptocurrency
    description: 'Vendor payout'     // Optional description
);

if ($payout) {
    $order->update([
        'vendor_payout_id' => $payout['id'],
        'vendor_payment_at' => now(),
    ]);
} else {
    // Log for manual processing - don't block the operation
    Log::error("Payout failed for order {$order->id}");
}
```

### Payout Error Handling

**CRITICAL:** Payout failures should NEVER block the main operation:

```php
// CORRECT - Don't block on payout failure
public function markAsCompleted()
{
    $this->status = self::STATUS_COMPLETED;
    $this->save();
    
    // Payout is best-effort, log failure for manual processing
    $this->processVendorPayment(); // Returns false on failure, but doesn't throw
    
    return true;
}

// WRONG - Don't do this
public function markAsCompleted()
{
    if (!$this->processVendorPayment()) {
        throw new Exception('Payout failed'); // DON'T block!
    }
    // ...
}
```

### Payout Statuses

| Status | Description |
|--------|-------------|
| `PROCESSING` | Payout initiated, being processed |
| `FINISHED` | Payout complete, funds sent |
| `FAILED` | Payout failed |

### Failed Payout Recovery

When a payout fails, the system logs the error and saves payment details for manual processing. To find orders/applications with failed payouts:

```php
// Orders with failed vendor payouts
Orders::whereNotNull('vendor_payment_at')
    ->whereNull('vendor_payout_id')
    ->get();

// Orders with failed buyer refunds
Orders::whereNotNull('buyer_refund_at')
    ->whereNull('buyer_payout_id')
    ->get();
```

These records contain the intended payout amount and address, allowing manual processing through the NowPayments dashboard.

## Payment Types

When creating payments, use the `case` parameter to identify payment type:

| Case | Description | Webhook Handler |
|------|-------------|-----------------|
| `order` | Order payment | `processOrderPayment()` |
| `vendor_fee` | Vendor registration fee | `processVendorFeePayment()` |
| `advertisement` | Advertisement payment | `processAdvertisementPayment()` |

## Monero RPC Migration

**DO NOT USE** Monero RPC for any of these operations:

| Operation | Old (Monero RPC) | New (NowPayments) |
|-----------|------------------|-------------------|
| Create payment address | `walletRPC->create_address()` | `createPayment()` |
| Check payment | `walletRPC->get_transfers()` | Webhook callback |
| Send to vendor | `walletRPC->transfer()` | `createPayout()` |
| Send refund | `walletRPC->transfer()` | `createPayout()` |

**Address Validation:** Use the `Cryptonote` library for Monero address validation - this does NOT require RPC.

## Related Files

- `config/nowpayments.php` - Configuration
- `app/Services/NowPaymentsService.php` - API client
- `app/Http/Controllers/WebhookController.php` - IPN handler
- `app/Models/Orders.php` - Order model with payout methods
- `app/Http/Controllers/VendorController.php` - Advertisement payments
- `app/Http/Controllers/AdminController.php` - Application refunds
- `routes/web.php` - Webhook route definition
