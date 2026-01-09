# Requirements Document

## Introduction

This document specifies the requirements for migrating the Kabus marketplace payment system from a self-hosted Monero RPC wallet to the NowPayments.io hosted cryptocurrency payment gateway. The migration will replace direct Monero wallet RPC calls with NowPayments API integration, enabling payment processing without maintaining local wallet infrastructure.

## Glossary

- **NowPayments_Service**: The service class responsible for communicating with the NowPayments.io API
- **Webhook_Controller**: The controller that handles incoming IPN (Instant Payment Notification) callbacks from NowPayments
- **Payment_Gateway**: The NowPayments.io API that processes cryptocurrency payments
- **IPN**: Instant Payment Notification - webhook callbacks sent by NowPayments when payment status changes
- **Order**: A purchase transaction between a buyer and vendor
- **Vendor_Payment**: A payment made by a user to become a vendor on the marketplace
- **Payment_Status**: The current state of a payment (waiting, confirming, confirmed, finished, failed, expired, partially_paid)

## Requirements

### Requirement 1: Configuration Management

**User Story:** As a system administrator, I want to configure NowPayments API credentials through environment variables, so that I can securely manage API keys without hardcoding them.

#### Acceptance Criteria

1. THE Configuration_System SHALL load the NowPayments API key from the `NOWPAYMENTS_API_KEY` environment variable
2. THE Configuration_System SHALL load the IPN secret key from the `NOWPAYMENTS_IPN_SECRET` environment variable
3. WHEN `NOWPAYMENTS_ENV` is set to "sandbox", THE Configuration_System SHALL use the sandbox API URL `https://api-sandbox.nowpayments.io/v1/`
4. WHEN `NOWPAYMENTS_ENV` is set to "live", THE Configuration_System SHALL use the production API URL `https://api.nowpayments.io/v1/`
5. THE Configuration_System SHALL provide a config file at `config/nowpayments.php` exposing all NowPayments settings

### Requirement 2: Database Schema Migration

**User Story:** As a developer, I want the database schema updated to support NowPayments payment data, so that payment information can be properly stored and tracked.

#### Acceptance Criteria

1. THE Migration SHALL add a `np_payment_id` column to the `orders` table for storing the NowPayments payment identifier
2. THE Migration SHALL add a `pay_address` column to the `orders` table for storing the deposit address
3. THE Migration SHALL add a `pay_amount` column to the `orders` table for storing the exact cryptocurrency amount to pay
4. THE Migration SHALL add a `pay_currency` column to the `orders` table defaulting to 'xmr'
5. THE Migration SHALL add a `np_payment_id` column to the `vendor_payment_subaddresses` table
6. THE Migration SHALL add a `pay_currency` column to the `vendor_payment_subaddresses` table defaulting to 'xmr'
7. THE Migration SHALL remove the `address_index` column from the `vendor_payment_subaddresses` table
8. THE Migration SHALL remove the `payment_address_index` column from the `orders` table

### Requirement 3: NowPayments Service Implementation

**User Story:** As a developer, I want a service class that handles NowPayments API communication, so that payment creation is abstracted and reusable.

#### Acceptance Criteria

1. THE NowPayments_Service SHALL provide a `createPayment` method that accepts price amount, price currency, pay currency, order ID, and payment case type
2. WHEN `createPayment` is called, THE NowPayments_Service SHALL send a POST request to the NowPayments `/payment` endpoint with the required parameters
3. WHEN the API request succeeds, THE NowPayments_Service SHALL return the response containing `payment_id`, `pay_address`, and `pay_amount`
4. WHEN the API request fails, THE NowPayments_Service SHALL log the error and return null
5. THE NowPayments_Service SHALL include the `ipn_callback_url` parameter pointing to the webhook endpoint in all payment creation requests
6. THE NowPayments_Service SHALL include the `x-api-key` header with the configured API key in all requests

### Requirement 4: Order Payment Processing

**User Story:** As a buyer, I want to pay for orders using NowPayments, so that I can complete purchases without the marketplace needing a local Monero wallet.

#### Acceptance Criteria

1. WHEN a buyer views an unpaid order, THE Orders_Controller SHALL check if a NowPayments payment has been created
2. IF no NowPayments payment exists for the order, THE Orders_Controller SHALL call the NowPayments_Service to create a new payment
3. WHEN a payment is created, THE Orders_Controller SHALL store the `np_payment_id`, `pay_address`, and `pay_amount` in the order record
4. THE Orders_Controller SHALL display the payment address and exact amount to the buyer
5. THE Orders_Controller SHALL generate a QR code containing the Monero payment URI with the address and amount
6. THE Orders_Controller SHALL NOT poll for payment status (webhook handles this)

### Requirement 5: Vendor Fee Payment Processing

**User Story:** As a user wanting to become a vendor, I want to pay the vendor fee using NowPayments, so that I can complete the vendor registration process.

#### Acceptance Criteria

1. WHEN a user initiates vendor payment, THE BecomeVendor_Controller SHALL call the NowPayments_Service to create a payment for the vendor fee
2. THE BecomeVendor_Controller SHALL store the `np_payment_id` and payment address in the vendor payment record
3. THE BecomeVendor_Controller SHALL display the payment address and required amount to the user
4. THE BecomeVendor_Controller SHALL generate a QR code for the payment address
5. THE BecomeVendor_Controller SHALL NOT poll the Monero RPC for incoming transactions

### Requirement 6: Webhook Handler Implementation

**User Story:** As a system, I want to receive and process payment notifications from NowPayments, so that order and vendor payment statuses are automatically updated.

#### Acceptance Criteria

1. THE Webhook_Controller SHALL expose a POST endpoint at `/api/webhooks/nowpayments`
2. WHEN a webhook is received, THE Webhook_Controller SHALL validate the HMAC-SHA512 signature using the IPN secret key
3. IF the signature is invalid, THE Webhook_Controller SHALL return a 403 response and log the invalid attempt
4. WHEN the payment status is "finished" or "confirmed", THE Webhook_Controller SHALL update the corresponding order or vendor payment as paid
5. THE Webhook_Controller SHALL identify whether the payment is for an order or vendor fee using the `order_id` field
6. WHEN an order payment is confirmed, THE Webhook_Controller SHALL update the order status to "payment_received" and set `is_paid` to true
7. WHEN a vendor fee payment is confirmed, THE Webhook_Controller SHALL set `payment_completed` to true on the vendor payment record
8. THE Webhook_Controller SHALL return a 200 response with status "ok" for valid webhooks

### Requirement 7: CSRF Exemption for Webhooks

**User Story:** As a system, I want the webhook endpoint to be exempt from CSRF protection, so that NowPayments can send POST requests without a CSRF token.

#### Acceptance Criteria

1. THE Middleware_Configuration SHALL exclude the path `api/webhooks/*` from CSRF token validation
2. THE Webhook_Controller SHALL accept POST requests from NowPayments without requiring authentication

### Requirement 8: Remove Monero RPC Dependencies

**User Story:** As a developer, I want all Monero RPC dependencies removed from the payment flow, so that the system no longer requires a local Monero wallet.

#### Acceptance Criteria

1. THE Orders_Controller SHALL NOT import or use the `MoneroIntegrations\MoneroPhp\walletRPC` class for payment processing
2. THE BecomeVendor_Controller SHALL NOT import or use the `MoneroIntegrations\MoneroPhp\walletRPC` class
3. THE Orders_Controller SHALL NOT call `create_address` or `get_transfers` RPC methods
4. THE BecomeVendor_Controller SHALL NOT call `create_address` or `get_transfers` RPC methods
5. THE Orders_Model SHALL NOT use walletRPC for `generatePaymentAddress` or `checkPayments` methods

### Requirement 9: View Updates for Payment Display

**User Story:** As a buyer, I want to see the correct payment information on the order page, so that I can send the exact amount to the correct address.

#### Acceptance Criteria

1. THE Order_View SHALL display the `pay_amount` from the order record as the exact amount to send
2. THE Order_View SHALL display the `pay_address` from the order record as the payment address
3. THE Order_View SHALL display a QR code encoding the Monero payment URI
4. THE Order_View SHALL display the current payment status
5. THE Order_View SHALL instruct users to refresh the page to check for status updates

### Requirement 10: Error Handling and Logging

**User Story:** As a system administrator, I want comprehensive error logging for payment operations, so that I can diagnose and resolve payment issues.

#### Acceptance Criteria

1. WHEN a NowPayments API call fails, THE NowPayments_Service SHALL log the error response body
2. WHEN webhook signature validation fails, THE Webhook_Controller SHALL log the received and calculated signatures
3. WHEN a payment cannot be created, THE Controller SHALL display an appropriate error message to the user
4. THE System SHALL NOT expose API keys or sensitive data in error messages shown to users
