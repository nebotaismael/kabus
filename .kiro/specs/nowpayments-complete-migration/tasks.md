# Implementation Tasks

## Task 1: Add Payout Method to NowPaymentsService

### Task 1.1: Implement createPayout method
- [ ] Add `createPayout(string $address, float $amount, string $currency, ?string $description)` method
- [ ] Send POST request to NowPayments `/payout` endpoint
- [ ] Include `x-api-key` header and payout parameters
- [ ] Return payout details (id, status, hash) on success
- [ ] Return null and log error on failure

### Task 1.2: Add payout configuration
- [ ] Add `payout_enabled` config option to `config/nowpayments.php`
- [ ] Add `payout_callback_url` config option

## Task 2: Create Database Migration for Payout Tracking

### Task 2.1: Create migration file
- [ ] Add `vendor_payout_id` column to orders table
- [ ] Add `buyer_payout_id` column to orders table
- [ ] Add `refund_payout_id` column to vendor_payment_subaddresses table
- [ ] Add `np_payment_id` column to advertisements table
- [ ] Add `pay_address` column to advertisements table
- [ ] Add `pay_amount` column to advertisements table
- [ ] Add `pay_currency` column to advertisements table
- [ ] Drop `payment_address_index` column from advertisements table
- [ ] Add index on `np_payment_id` for advertisements table

## Task 3: Update Orders Model for NowPayments Payouts

### Task 3.1: Update processVendorPayment method
- [ ] Remove Monero RPC initialization
- [ ] Inject or resolve NowPaymentsService
- [ ] Call `createPayout()` with vendor address and calculated amount
- [ ] Store `vendor_payout_id` on success
- [ ] Log error but don't block on failure

### Task 3.2: Update processBuyerRefund method
- [ ] Remove Monero RPC initialization
- [ ] Inject or resolve NowPaymentsService
- [ ] Call `createPayout()` with buyer address and calculated refund amount
- [ ] Store `buyer_payout_id` on success
- [ ] Log error but don't block on failure

### Task 3.3: Remove deprecated methods
- [ ] Remove `generatePaymentAddress()` method if present
- [ ] Remove `checkPayments()` method if present
- [ ] Remove any Monero RPC imports

## Task 4: Update AdminController for NowPayments Refunds

### Task 4.1: Update denyVendorApplication method
- [ ] Remove Monero RPC initialization
- [ ] Inject NowPaymentsService via method injection
- [ ] Call `createPayout()` with applicant address and refund amount
- [ ] Store `refund_payout_id` on success
- [ ] Update error message for failed refunds

## Task 5: Update VendorController for Advertisement Payments

### Task 5.1: Remove walletRPC from VendorController
- [ ] Remove `$walletRPC` property
- [ ] Remove walletRPC initialization from constructor
- [ ] Remove Monero RPC import

### Task 5.2: Update createAdvertisement method
- [ ] Inject NowPaymentsService via method injection
- [ ] Replace `create_address()` call with `createPayment()` call
- [ ] Use case='advertisement' and order_id=payment_identifier
- [ ] Store `np_payment_id`, `pay_address`, `pay_amount` in advertisement
- [ ] Remove `payment_address_index` usage

### Task 5.3: Update showAdvertisementPayment method
- [ ] Remove `get_transfers()` RPC call
- [ ] Remove payment polling logic
- [ ] Display payment status from database only
- [ ] Add instruction to refresh page for status updates

## Task 6: Update WebhookController for Advertisement Payments

### Task 6.1: Add advertisement payment detection
- [ ] Add `isAdvertisementPayment(?string $orderId): bool` method
- [ ] Check if order_id matches an advertisement's payment_identifier

### Task 6.2: Add advertisement payment processing
- [ ] Add `processAdvertisementPayment(string $orderId, array $payload): void` method
- [ ] Find advertisement by payment_identifier
- [ ] Set `payment_completed = true`
- [ ] Set `payment_completed_at = now()`
- [ ] Set `starts_at = now()`
- [ ] Set `ends_at = now()->addDays(duration_days)`
- [ ] Store `total_received` from webhook payload

### Task 6.3: Update handle method
- [ ] Add advertisement payment check in the payment type identification
- [ ] Call `processAdvertisementPayment()` for advertisement webhooks

## Task 7: Update Advertisement Model

### Task 7.1: Update fillable array
- [x] Add `np_payment_id` to fillable
- [x] Add `pay_address` to fillable
- [x] Add `pay_amount` to fillable
- [x] Add `pay_currency` to fillable
- [x] Remove `payment_address_index` from fillable

### Task 7.2: Update casts array
- [x] Add `pay_amount` cast to decimal:12

## Task 8: Update Advertisement Payment View

### Task 8.1: Update payment display
- [x] Use `pay_address` instead of `payment_address`
- [x] Use `pay_amount` for exact amount display
- [x] Remove partial payment tracking display
- [x] Add refresh instruction for status updates

## Task 9: Checkpoint - Verify No Monero RPC in Payment Flow

### Task 9.1: Verify Orders model
- [x] Confirm no MoneroIntegrations imports
- [x] Confirm no walletRPC instantiation

### Task 9.2: Verify VendorController
- [x] Confirm no MoneroIntegrations imports
- [x] Confirm no walletRPC property or usage

### Task 9.3: Verify AdminController
- [x] Confirm no MoneroIntegrations imports in denyVendorApplication
- [x] Confirm no walletRPC instantiation

## Task 10: Update Documentation

### Task 10.1: Update .env.example
- [x] Add `NOWPAYMENTS_PAYOUT_ENABLED=true` example
- [x] Add comment about payout API requirements

### Task 10.2: Update steering documentation
- [x] Update `.kiro/steering/nowpayments.md` with payout information
