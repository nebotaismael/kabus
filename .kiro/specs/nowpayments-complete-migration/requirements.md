# Requirements Document

## Introduction

This document specifies the requirements for completing the migration of all remaining Monero RPC functionality to NowPayments.io. The initial migration covered order payments and vendor fee payments. This phase covers vendor payouts, buyer refunds, advertisement payments, and address validation.

## Glossary

- **NowPayments_Service**: The service class responsible for communicating with the NowPayments.io API
- **Payout**: A payment sent from the marketplace to a vendor or buyer (withdrawal)
- **Vendor_Payout**: Payment sent to vendor when an order is completed
- **Buyer_Refund**: Payment sent to buyer when an order is cancelled
- **Advertisement_Payment**: Payment made by vendor to advertise a product
- **Cryptonote**: Monero address validation library

## Requirements

### Requirement 1: NowPayments Payout Service

**User Story:** As a system, I want to process payouts through NowPayments, so that vendor payments and buyer refunds can be processed without a local Monero wallet.

#### Acceptance Criteria

1. THE NowPayments_Service SHALL provide a `createPayout` method that accepts recipient address, amount, and description
2. WHEN `createPayout` is called, THE NowPayments_Service SHALL send a POST request to the NowPayments payout endpoint
3. WHEN the payout request succeeds, THE NowPayments_Service SHALL return the payout details including payout_id and status
4. WHEN the payout request fails, THE NowPayments_Service SHALL log the error and return null
5. THE NowPayments_Service SHALL include the `x-api-key` header with the configured API key in all payout requests

### Requirement 2: Vendor Payout Processing

**User Story:** As a vendor, I want to receive payment automatically when an order is completed, so that I get paid for my sales.

#### Acceptance Criteria

1. WHEN an order is marked as completed, THE Orders_Model SHALL call the NowPayments payout service
2. THE Orders_Model SHALL calculate the vendor payment amount as total received minus commission
3. THE Orders_Model SHALL retrieve a random return address from the vendor's saved addresses
4. WHEN the payout succeeds, THE Orders_Model SHALL update the order with payout details
5. WHEN the payout fails, THE Orders_Model SHALL log the error for manual processing
6. THE Orders_Model SHALL NOT use Monero RPC for vendor payouts

### Requirement 3: Buyer Refund Processing

**User Story:** As a buyer, I want to receive a refund automatically when an order is cancelled, so that I get my money back.

#### Acceptance Criteria

1. WHEN an order is cancelled after payment, THE Orders_Model SHALL call the NowPayments payout service
2. THE Orders_Model SHALL calculate the refund amount as total received minus cancellation fee
3. THE Orders_Model SHALL retrieve a random return address from the buyer's saved addresses
4. WHEN the refund succeeds, THE Orders_Model SHALL update the order with refund details
5. WHEN the refund fails, THE Orders_Model SHALL log the error for manual processing
6. THE Orders_Model SHALL NOT use Monero RPC for buyer refunds

### Requirement 4: Admin Refund Processing

**User Story:** As an admin, I want to process refunds for denied vendor applications through NowPayments, so that applicants get their money back.

#### Acceptance Criteria

1. WHEN a vendor application is denied, THE Admin_Controller SHALL call the NowPayments payout service
2. THE Admin_Controller SHALL calculate the refund amount based on the configured refund percentage
3. WHEN the refund succeeds, THE Admin_Controller SHALL update the application with refund details
4. WHEN the refund fails, THE Admin_Controller SHALL log the error and notify admin for manual processing
5. THE Admin_Controller SHALL NOT use Monero RPC for application refunds

### Requirement 5: Advertisement Payment Processing

**User Story:** As a vendor, I want to pay for product advertisements using NowPayments, so that I can promote my products.

#### Acceptance Criteria

1. WHEN a vendor creates an advertisement, THE Vendor_Controller SHALL call NowPayments to create a payment
2. THE Vendor_Controller SHALL store the NowPayments payment_id and pay_address in the advertisement record
3. THE Vendor_Controller SHALL display the payment address and amount to the vendor
4. THE Vendor_Controller SHALL NOT poll for payment status (webhook handles this)
5. THE Vendor_Controller SHALL NOT use Monero RPC for advertisement payments

### Requirement 6: Advertisement Webhook Processing

**User Story:** As a system, I want to receive payment notifications for advertisements, so that they are activated automatically.

#### Acceptance Criteria

1. THE Webhook_Controller SHALL identify advertisement payments using the order_id field
2. WHEN an advertisement payment is confirmed, THE Webhook_Controller SHALL mark the advertisement as paid
3. WHEN an advertisement payment is confirmed, THE Webhook_Controller SHALL set the start and end dates
4. THE Webhook_Controller SHALL return a 200 response for valid advertisement webhooks

### Requirement 7: Monero Address Validation

**User Story:** As a user, I want my Monero addresses validated without requiring a local Monero node, so that I can add return addresses reliably.

#### Acceptance Criteria

1. THE ReturnAddress_Controller SHALL validate Monero addresses using the Cryptonote library
2. THE ReturnAddress_Controller SHALL NOT require a Monero RPC connection for address validation
3. THE validation SHALL check address format and checksum

### Requirement 8: Remove Deprecated Methods

**User Story:** As a developer, I want all deprecated Monero RPC methods removed, so that the codebase is clean.

#### Acceptance Criteria

1. THE Orders_Model SHALL remove the deprecated `generatePaymentAddress` method
2. THE Orders_Model SHALL remove the deprecated `checkPayments` method
3. THE Vendor_Controller SHALL remove the `walletRPC` property and constructor initialization
4. THE Admin_Controller SHALL NOT import or use the Monero RPC library

### Requirement 9: Database Schema Updates

**User Story:** As a developer, I want the database schema updated to support NowPayments for advertisements, so that payment data is properly stored.

#### Acceptance Criteria

1. THE Migration SHALL add `np_payment_id` column to the `advertisements` table
2. THE Migration SHALL add `pay_currency` column to the `advertisements` table
3. THE Migration SHALL remove `payment_address_index` column from the `advertisements` table

### Requirement 10: Error Handling

**User Story:** As a system administrator, I want comprehensive error handling for all payout operations, so that failed payouts can be processed manually.

#### Acceptance Criteria

1. WHEN a payout fails, THE System SHALL log the error with full details
2. WHEN a payout fails, THE System SHALL NOT block the main operation (order completion, cancellation)
3. THE System SHALL provide a way to identify orders/applications with failed payouts
