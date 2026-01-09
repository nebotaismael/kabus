Here is a comprehensive **Context & Implementation Package** designed to be pasted directly into a GitHub Copilot chat or attached as a context file (`CONTEXT.md`) for an AI agent.

It contains the **System Overview** (so the AI understands what to rip out) and the **Implementation Specifications** (so it knows exactly what to build).

---

# CONTEXT.md: Migration from Monero RPC to NowPayments.io

## 1. System Overview (Current State)

**Application:** Kabus (Laravel 10/11 Monero Marketplace Script)
**Current Payment Architecture:** Self-hosted Monero Node + Wallet RPC.

### How it currently works:

1. **Dependencies:** Uses `monero-integrations/monerophp` library to communicate with a local `monero-wallet-rpc` service running on port 18082.
2. **Vendor Fees (`BecomeVendorController.php`):**
* Calls `$this->walletRPC->create_address()` to generate a unique subaddress for the user.
* Stores `address_index` and `address` in `vendor_payment_subaddresses` table.
* **Verification:** Checks for incoming transfers by polling the RPC (`checkIncomingTransaction` method) every time the user visits the page.


3. **Order Payments (`OrdersController.php`):**
* Generates a subaddress for the order.
* **Verification:** Polls the RPC to check if funds have arrived at that specific index.


4. **Database:** Rely on `address_index` columns to map payments to users/orders.

## 2. Migration Goal (Desired State)

**New Payment Architecture:** NowPayments.io API (Hosted Crypto Payments).

### How it will work:

1. **Dependencies:** Standard Laravel `Http` client (Guzzle). No local RPC required.
2. **Flow:**
* Backend calls NowPayments API (`POST /payment`) with the USD amount.
* API returns a `payment_id`, `pay_address` (deposit address), and `pay_amount` (calculated crypto amount).
* Store these details in the database.


3. **Verification:**
* **No Polling:** The system stops checking the wallet actively.
* **Webhooks:** NowPayments sends an IPN (Instant Payment Notification) to `https://market.url/api/webhooks/nowpayments` when a payment is detected/confirmed.
* The `WebhookController` updates the order/vendor status in the database.



---

## 3. Implementation Instructions (For Copilot)

**Task:** Refactor the codebase to remove Monero RPC dependencies and implement the NowPayments Service and Webhook logic.

### Step 1: Configuration & Environment

**Action:** Create `config/nowpayments.php` and update `.env`.

**File:** `.env` (Append these)

```ini
NOWPAYMENTS_API_KEY=your_api_key_here
NOWPAYMENTS_IPN_SECRET=your_ipn_secret_here
NOWPAYMENTS_ENV=sandbox 
# Set NOWPAYMENTS_ENV=live for production

```

**File:** `config/nowpayments.php`

```php
<?php

return [
    'api_key' => env('NOWPAYMENTS_API_KEY'),
    'ipn_secret' => env('NOWPAYMENTS_IPN_SECRET'),
    'api_url' => env('NOWPAYMENTS_ENV') === 'sandbox' 
        ? 'https://api-sandbox.nowpayments.io/v1/' 
        : 'https://api.nowpayments.io/v1/',
];

```

### Step 2: Database Migration

**Action:** Create a migration to replace Monero columns with NowPayments columns.

**Command:** `php artisan make:migration switch_payment_system_to_nowpayments`

**File:** `database/migrations/xxxx_xx_xx_switch_payment_system_to_nowpayments.php`

```php
public function up()
{
    // Update Orders Table
    Schema::table('orders', function (Blueprint $table) {
        $table->dropColumn(['address_index', 'payment_address']); // Remove Monero RPC cols
        
        $table->string('np_payment_id')->nullable()->index(); // NowPayments ID
        $table->string('pay_address')->nullable();            // Where user sends money
        $table->string('pay_currency')->default('xmr');       // Currency (e.g. xmr)
        $table->decimal('pay_amount', 16, 8)->nullable();     // Exact crypto amount
    });

    // Update Vendor Payments Table
    Schema::table('vendor_payment_subaddresses', function (Blueprint $table) {
        $table->dropColumn(['address_index']);
        
        $table->string('np_payment_id')->nullable()->index();
        // We keep 'address' column but it now stores the NowPayments deposit address
        $table->string('pay_currency')->default('xmr');
    });
}

```

### Step 3: The Service Class

**Action:** Create a service to handle API communication.

**File:** `app/Services/NowPaymentsService.php`

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NowPaymentsService
{
    protected $baseUrl;
    protected $apiKey;

    public function __construct()
    {
        $this->baseUrl = config('nowpayments.api_url');
        $this->apiKey = config('nowpayments.api_key');
    }

    public function createPayment($price_amount, $price_currency, $pay_currency, $order_id = null, $case = null)
    {
        // $case helps distinguish between 'order' and 'vendor_fee' in the webhook if needed
        $payload = [
            'price_amount' => $price_amount,
            'price_currency' => $price_currency,
            'pay_currency' => $pay_currency,
            'ipn_callback_url' => route('webhooks.nowpayments'),
            'order_id' => (string) $order_id, 
            'case' => $case 
        ];

        $response = Http::withHeaders([
            'x-api-key' => $this->apiKey,
            'Content-Type' => 'application/json',
        ])->post($this->baseUrl . 'payment', $payload);

        if ($response->failed()) {
            Log::error('NowPayments Error: ' . $response->body());
            return null;
        }

        return $response->json();
    }
}

```

### Step 4: Refactor `OrdersController`

**Action:** Remove RPC logic and inject `NowPaymentsService`.

**File:** `app/Http/Controllers/OrdersController.php`

**Remove:** `use MoneroIntegrations\MoneroPhp\walletRPC;`
**Add:** `use App\Services\NowPaymentsService;`

**Update Method:** `show($uniqueUrl)`

```php
public function show($uniqueUrl, NowPaymentsService $paymentService)
{
    $order = Orders::findByUrl($uniqueUrl);
    if (!$order) abort(404);

    // ... (Keep existing auth checks) ...

    $isBuyer = $order->user_id === Auth::id();

    // Payment Generation Logic
    if ($isBuyer && $order->status === Orders::STATUS_WAITING_PAYMENT) {
        
        // If no payment generated yet, generate one
        if (empty($order->np_payment_id)) {
            // Assume order total is in USD. 
            $payment = $paymentService->createPayment(
                $order->total_price, // Ensure this field exists or calculate it
                'usd', 
                'xmr', 
                $order->id,
                'order'
            );

            if ($payment) {
                $order->update([
                    'np_payment_id' => $payment['payment_id'],
                    'pay_address' => $payment['pay_address'],
                    'pay_amount' => $payment['pay_amount'],
                    'pay_currency' => 'xmr'
                ]);
            }
        }
    }

    // QR Code Generation (Using new address)
    $qrCode = null;
    if ($order->pay_address && $order->pay_amount) {
        $uri = "monero:{$order->pay_address}?tx_amount={$order->pay_amount}";
        $qrCode = $this->generateQrCode($uri);
    }

    return view('orders.show', [
        'order' => $order,
        'isBuyer' => $isBuyer,
        'qrCode' => $qrCode,
        // ... (other variables)
    ]);
}

```

### Step 5: Refactor `BecomeVendorController`

**Action:** Similar to Orders, switch from RPC creation to API creation.

**File:** `app/Http/Controllers/BecomeVendorController.php`

**Update Method:** `createVendorPayment`

```php
private function createVendorPayment(User $user, NowPaymentsService $paymentService)
{
    $requiredAmountUsd = 100; // Example: Fixed USD fee instead of dynamic XMR
    // Or fetch dynamic XMR conversion if preferred
    
    $payment = $paymentService->createPayment(
        $requiredAmountUsd, 
        'usd', 
        'xmr', 
        $user->id, 
        'vendor_fee'
    );

    if (!$payment) throw new \Exception("Payment gateway error");

    $vendorPayment = new VendorPayment([
        'user_id' => $user->id,
        'np_payment_id' => $payment['payment_id'],
        'address' => $payment['pay_address'], // Reusing 'address' column
        'pay_currency' => 'xmr',
        'expires_at' => Carbon::now()->addHours(24),
    ]);
    
    $vendorPayment->save();
    return $vendorPayment;
}

```

**Note:** Remove `checkIncomingTransaction` method entirely.

### Step 6: The Webhook Handler (The Brain)

**Action:** Create a controller to listen for NowPayments callbacks.

**File:** `app/Http/Controllers/WebhookController.php`

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Orders;
use App\Models\VendorPayment;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function handle(Request $request)
    {
        // 1. Validate Signature
        $secret = config('nowpayments.ipn_secret');
        if (empty($secret)) {
            Log::error('NowPayments webhook: Secret not set');
            return response()->json(['error' => 'Config error'], 500);
        }

        $receivedSig = $request->header('x-nowpayments-sig');
        $requestData = $request->all();
        ksort($requestData);
        $sortedJson = json_encode($requestData, JSON_UNESCAPED_SLASHES);
        $calcSig = hash_hmac('sha512', $sortedJson, $secret);

        if ($receivedSig !== $calcSig) {
            Log::warning("NowPayments Invalid Signature. Calc: $calcSig | Rec: $receivedSig");
            return response()->json(['error' => 'Invalid signature'], 403);
        }

        // 2. Process Data
        $status = $requestData['payment_status'];
        $orderId = $requestData['order_id'];
        
        // 3. Handle Status
        if ($status === 'finished' || $status === 'confirmed') {
            // Determine if this is an Order or Vendor Fee
            // Strategy: Try to find order first
            $order = Orders::find($orderId);
            
            if ($order) {
                if (!$order->is_paid) {
                    $order->status = 'processing'; // Update status
                    $order->is_paid = true;
                    $order->save();
                    Log::info("Order #$orderId marked as paid via webhook.");
                }
            } else {
                // Try finding vendor payment (using user_id which was passed as order_id for vendor fees)
                $vendorPay = VendorPayment::where('user_id', $orderId)
                                          ->where('np_payment_id', $requestData['payment_id'])
                                          ->first();
                if ($vendorPay && !$vendorPay->payment_completed) {
                    $vendorPay->payment_completed = true;
                    $vendorPay->save();
                    Log::info("Vendor fee for User #$orderId marked as paid.");
                }
            }
        }

        return response()->json(['status' => 'ok']);
    }
}

```

### Step 7: Routing & CSRF

**Action:** Register the webhook and exclude it from CSRF protection (since NowPayments sends the POST).

**File:** `routes/web.php`

```php
use App\Http\Controllers\WebhookController;

Route::post('/api/webhooks/nowpayments', [WebhookController::class, 'handle'])->name('webhooks.nowpayments');

```

**File:** `bootstrap/app.php` (Laravel 11) OR `app/Http/Middleware/VerifyCsrfToken.php` (Laravel 10)

*If Laravel 11 (`bootstrap/app.php`):*

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->validateCsrfTokens(except: [
        'api/webhooks/*',
    ]);
})

```

*If Laravel 10 (`VerifyCsrfToken.php`):*

```php
protected $except = [
    'api/webhooks/*',
];

```

### Step 8: View Updates

**Action:** Update `resources/views/orders/show.blade.php`.
Replace the old XMR amount display logic with:

```blade
<div class="payment-info">
    <p>Please send exactly <strong>{{ $order->pay_amount }} XMR</strong></p>
    <p>Address: {{ $order->pay_address }}</p>
    <div class="qr-code">
        <img src="{{ $qrCode }}" alt="Payment QR">
    </div>
    <p class="status">Status: {{ ucfirst($order->status) }}</p>
    <small>Payment is processed automatically. Refresh page to check status.</small>
</div>

```