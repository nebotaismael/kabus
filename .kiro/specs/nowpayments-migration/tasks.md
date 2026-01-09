# Implementation Plan: NowPayments.io Migration

## Overview

This implementation plan migrates the Kabus marketplace from Monero RPC to NowPayments.io API. Tasks are ordered to build foundational components first, then integrate them into existing controllers, and finally clean up legacy code.

## Tasks

- [x] 1. Set up configuration and environment
  - [x] 1.1 Create `config/nowpayments.php` configuration file
    - Define api_key, ipn_secret, api_url settings
    - Support sandbox/live environment switching
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5_
  - [x] 1.2 Update `.env.example` with NowPayments environment variables
    - Add NOWPAYMENTS_API_KEY, NOWPAYMENTS_IPN_SECRET, NOWPAYMENTS_ENV
    - _Requirements: 1.1, 1.2_

- [x] 2. Create database migration
  - [x] 2.1 Create migration file for NowPayments schema changes
    - Add np_payment_id, pay_address, pay_amount, pay_currency to orders table
    - Add np_payment_id, pay_currency to vendor_payment_subaddresses table
    - Remove payment_address_index from orders table
    - Remove address_index from vendor_payment_subaddresses table
    - Add indexes on np_payment_id columns
    - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 2.6, 2.7, 2.8_

- [x] 3. Implement NowPaymentsService
  - [x] 3.1 Create `app/Services/NowPaymentsService.php`
    - Implement constructor to load config
    - Implement `createPayment()` method with HTTP client
    - Include x-api-key header and ipn_callback_url in requests
    - Handle success and error responses
    - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 3.6_
  - [ ]* 3.2 Write property test for API request formation
    - **Property 1: Payment Request Formation**
    - **Validates: Requirements 3.2, 3.5, 3.6**
  - [ ]* 3.3 Write property test for response handling
    - **Property 2: Successful API Response Handling**
    - **Property 3: Failed API Response Handling**
    - **Validates: Requirements 3.3, 3.4**

- [x] 4. Implement WebhookController
  - [x] 4.1 Create `app/Http/Controllers/WebhookController.php`
    - Implement `handle()` method for POST requests
    - Implement HMAC-SHA512 signature validation
    - Implement recursive key sorting for payload
    - Handle order and vendor fee payment updates
    - Return appropriate HTTP responses
    - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5, 6.6, 6.7, 6.8_
  - [ ]* 4.2 Write property test for signature validation
    - **Property 4: Webhook Signature Validation**
    - **Property 5: Invalid Signature Rejection**
    - **Validates: Requirements 6.2, 6.3**
  - [ ]* 4.3 Write property test for webhook processing
    - **Property 6: Valid Webhook Processing**
    - **Validates: Requirements 6.4, 6.5, 6.8**

- [x] 5. Configure routing and CSRF exemption
  - [x] 5.1 Add webhook route to `routes/web.php`
    - Register POST route for `/api/webhooks/nowpayments`
    - _Requirements: 6.1_
  - [x] 5.2 Configure CSRF exemption for webhook endpoint
    - Add `api/webhooks/*` to CSRF exceptions
    - _Requirements: 7.1, 7.2_

- [x] 6. Checkpoint - Verify foundation components
  - Ensure all tests pass, ask the user if questions arise.
  - Run migration on test database
  - Verify NowPaymentsService can be instantiated
  - Verify webhook route is accessible

- [x] 7. Update Orders model
  - [x] 7.1 Update `app/Models/Orders.php` fillable and casts
    - Add np_payment_id, pay_address, pay_amount, pay_currency to fillable
    - Add appropriate casts for new fields
    - _Requirements: 2.1, 2.2, 2.3, 2.4_
  - [x] 7.2 Remove Monero RPC methods from Orders model
    - Remove or deprecate `generatePaymentAddress()` method
    - Remove or deprecate `checkPayments()` method
    - _Requirements: 8.5_

- [x] 8. Update VendorPayment model
  - [x] 8.1 Update `app/Models/VendorPayment.php` fillable and casts
    - Add np_payment_id, pay_currency to fillable
    - Remove address_index from fillable
    - _Requirements: 2.5, 2.6, 2.7_

- [x] 9. Refactor OrdersController
  - [x] 9.1 Remove Monero RPC dependencies from OrdersController
    - Remove walletRPC import and property
    - Remove walletRPC initialization from constructor
    - _Requirements: 8.1, 8.3_
  - [x] 9.2 Update `show()` method to use NowPaymentsService
    - Inject NowPaymentsService via method injection
    - Replace payment address generation with NowPaymentsService call
    - Store np_payment_id, pay_address, pay_amount in order
    - Remove payment polling logic
    - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5, 4.6_
  - [x] 9.3 Update QR code generation for NowPayments
    - Generate Monero URI with pay_address and pay_amount
    - _Requirements: 4.5_
  - [ ]* 9.4 Write property test for order payment update
    - **Property 7: Order Status Update on Payment**
    - **Validates: Requirements 6.6**

- [x] 10. Refactor BecomeVendorController
  - [x] 10.1 Remove Monero RPC dependencies from BecomeVendorController
    - Remove walletRPC import and property
    - Remove walletRPC initialization from constructor
    - Remove checkIncomingTransaction() method
    - _Requirements: 8.2, 8.4_
  - [x] 10.2 Update `createVendorPayment()` to use NowPaymentsService
    - Inject NowPaymentsService
    - Replace RPC address creation with NowPaymentsService call
    - Store np_payment_id and pay_address in vendor payment
    - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5_
  - [x] 10.3 Update `getCurrentVendorPayment()` method
    - Remove RPC transaction checking
    - Rely on webhook for payment confirmation
    - _Requirements: 5.5_
  - [ ]* 10.4 Write property test for vendor payment completion
    - **Property 8: Vendor Payment Completion**
    - **Validates: Requirements 6.7**

- [x] 11. Update views
  - [x] 11.1 Update `resources/views/orders/show.blade.php`
    - Display pay_amount from order record
    - Display pay_address from order record
    - Update QR code display
    - Add refresh instruction for status updates
    - _Requirements: 9.1, 9.2, 9.3, 9.4, 9.5_
  - [x] 11.2 Update vendor payment view
    - Display payment address and amount from NowPayments
    - Update QR code display
    - _Requirements: 5.3, 5.4_

- [x] 12. Checkpoint - Integration testing
  - Ensure all tests pass, ask the user if questions arise.
  - Test order creation and payment flow (with mocked API)
  - Test vendor fee payment flow (with mocked API)
  - Test webhook processing for both payment types

- [x] 13. Error handling and logging
  - [x] 13.1 Add comprehensive error logging to NowPaymentsService
    - Log API errors with response body
    - _Requirements: 10.1_
  - [x] 13.2 Add error logging to WebhookController
    - Log signature validation failures
    - _Requirements: 10.2_
  - [x] 13.3 Add user-friendly error messages to controllers
    - Display appropriate messages on payment creation failure
    - _Requirements: 10.3, 10.4_

- [x] 14. Final checkpoint - Complete testing
  - Ensure all tests pass, ask the user if questions arise.
  - Run full test suite
  - Verify no Monero RPC imports remain in payment flow
  - Manual testing with sandbox API (if credentials available)

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation
- Property tests validate universal correctness properties
- Unit tests validate specific examples and edge cases
- The migration is designed to be reversible - old columns can be kept temporarily
