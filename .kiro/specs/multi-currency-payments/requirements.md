# Requirements Document

## Introduction

This feature extends the payment system to support multiple cryptocurrencies through the NowPayments.io gateway. Currently, the marketplace only accepts Monero (XMR) for payments. This enhancement allows buyers to choose from a list of supported cryptocurrencies when making payments for orders, vendor registration fees, and advertisements.

## Glossary

- **Payment_System**: The NowPayments.io integration that handles cryptocurrency payment processing
- **Currency_Selector**: A UI component that displays available cryptocurrencies and allows users to select their preferred payment currency
- **Supported_Currency**: A cryptocurrency that NowPayments.io accepts for payments (e.g., XMR, BTC, LTC, ETH)
- **Payment_Confirmation_Page**: The page displayed to users showing payment address, amount, and QR code for completing payment
- **NowPayments_API**: The external API service that provides payment addresses and processes cryptocurrency payments

## Requirements

### Requirement 1

**User Story:** As a buyer, I want to select my preferred cryptocurrency when paying for an order, so that I can use the cryptocurrency I already own.

#### Acceptance Criteria

1. WHEN a buyer views the order payment page THEN the Payment_System SHALL display a Currency_Selector with all available Supported_Currencies
2. WHEN a buyer selects a Supported_Currency from the Currency_Selector THEN the Payment_System SHALL request a new payment address from NowPayments_API for that currency
3. WHEN the Payment_System receives a valid payment address THEN the Payment_Confirmation_Page SHALL display the payment amount in the selected Supported_Currency
4. WHEN the Payment_System receives a valid payment address THEN the Payment_Confirmation_Page SHALL display a QR code appropriate for the selected Supported_Currency
5. WHEN a buyer changes the selected Supported_Currency THEN the Payment_System SHALL generate a new payment address for the newly selected currency

### Requirement 2

**User Story:** As a vendor applicant, I want to pay the vendor registration fee using my preferred cryptocurrency, so that I can complete registration with the cryptocurrency I have available.

#### Acceptance Criteria

1. WHEN a vendor applicant views the vendor fee payment page THEN the Payment_System SHALL display a Currency_Selector with all available Supported_Currencies
2. WHEN a vendor applicant selects a Supported_Currency THEN the Payment_System SHALL create a vendor payment record with the selected currency
3. WHEN the vendor payment is created THEN the Payment_Confirmation_Page SHALL display the fee amount converted to the selected Supported_Currency

### Requirement 3

**User Story:** As a system administrator, I want to configure which cryptocurrencies are available for payments, so that I can control which payment options are offered to users.

#### Acceptance Criteria

1. WHEN the Payment_System initializes THEN the Payment_System SHALL load the list of Supported_Currencies from configuration
2. WHEN a Supported_Currency is disabled in configuration THEN the Currency_Selector SHALL exclude that currency from available options
3. WHEN no Supported_Currencies are configured THEN the Payment_System SHALL default to Monero (XMR) as the only payment option

### Requirement 4

**User Story:** As a buyer, I want to see clear payment instructions for my selected cryptocurrency, so that I can complete the payment correctly.

#### Acceptance Criteria

1. WHEN the Payment_Confirmation_Page displays THEN the Payment_System SHALL show the cryptocurrency symbol and full name
2. WHEN the Payment_Confirmation_Page displays THEN the Payment_System SHALL show the exact payment amount with appropriate decimal precision for the selected Supported_Currency
3. WHEN the Payment_Confirmation_Page displays THEN the Payment_System SHALL show the payment address in a format appropriate for the selected Supported_Currency
4. WHEN the selected Supported_Currency is not Monero THEN the QR code SHALL use the appropriate URI scheme for that cryptocurrency

### Requirement 5

**User Story:** As a system operator, I want payment currency information stored with each transaction, so that I can track which currencies are being used.

#### Acceptance Criteria

1. WHEN a payment is created THEN the Payment_System SHALL store the selected Supported_Currency code with the order or vendor payment record
2. WHEN a payment is completed THEN the Payment_System SHALL preserve the original currency selection in the transaction history
3. WHEN viewing order details THEN the Payment_System SHALL display the currency that was used for payment
