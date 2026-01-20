# Implementation Plan

- [x] 1. Extend NowPayments configuration and service




  - [ ] 1.1 Add supported currencies configuration to config/nowpayments.php
    - Add `supported_currencies` array with XMR, BTC, LTC, ETH, USDT


    - Include name, symbol, decimals, and uri_scheme for each currency
    - _Requirements: 3.1, 3.2, 3.3_
  - [ ] 1.2 Add currency helper methods to NowPaymentsService
    - Implement `getSupportedCurrencies()` method
    - Implement `isValidCurrency(string $currency)` method
    - Implement `getCurrencyConfig(string $currency)` method
    - Add fallback to XMR when no currencies configured
    - _Requirements: 3.1, 3.2, 3.3_
  - [x]* 1.3 Write property tests for currency configuration




    - **Property 1: Supported currencies match configuration**
    - **Property 2: Currency validation accepts only configured currencies**
    - **Property 3: Default currency fallback**
    - **Validates: Requirements 3.1, 3.2, 3.3**





- [ ] 2. Create currency selector Blade component
  - [ ] 2.1 Create reusable currency selector component
    - Create resources/views/components/currency-selector.blade.php


    - Accept currencies array, selected currency, and form action as parameters
    - Include noscript fallback button for form submission
    - Style consistently with existing payment pages
    - _Requirements: 1.1, 2.1, 4.1_

- [x] 3. Update order payment flow for multi-currency




  - [ ] 3.1 Modify OrdersController::show() to handle currency selection
    - Accept `currency` parameter from POST request
    - Validate currency against supported currencies
    - Pass selected currency to NowPayments createPayment call
    - Pass currency list and selected currency to view
    - _Requirements: 1.1, 1.2, 1.3, 1.5_
  - [x] 3.2 Update orders/show.blade.php payment section




    - Add currency selector component above payment details
    - Display currency symbol and name in payment information
    - Update amount display to use correct decimal precision
    - _Requirements: 1.1, 1.3, 4.1, 4.2_


  - [ ]* 3.3 Write property test for order currency storage
    - **Property 4: Payment creation stores selected currency**
    - **Validates: Requirements 1.2, 2.2, 5.1**






- [x] 4. Update QR code generation for multiple currencies



  - [ ] 4.1 Modify QR code generation to use currency-specific URI schemes
    - Update generateQrCode method in OrdersController
    - Update generateQrCode method in BecomeVendorController
    - Support monero:, bitcoin:, litecoin:, ethereum: URI schemes
    - Fall back to plain address for currencies without standard URI
    - _Requirements: 1.4, 4.4_
  - [ ]* 4.2 Write property test for QR code URI schemes
    - **Property 6: QR code URI scheme correctness**
    - **Validates: Requirements 1.4, 4.4**

- [ ] 5. Update vendor fee payment flow for multi-currency
  - [ ] 5.1 Modify BecomeVendorController::payment() to handle currency selection
    - Accept `currency` parameter from POST request
    - Validate currency against supported currencies
    - Pass selected currency to createVendorPayment method
    - Update createVendorPayment to use selected currency
    - _Requirements: 2.1, 2.2, 2.3_
  - [ ] 5.2 Update become-vendor/payment.blade.php
    - Add currency selector component
    - Display currency symbol and name in payment information
    - Update amount display formatting
    - _Requirements: 2.1, 2.3, 4.1, 4.2_

- [ ] 6. Add routes for currency selection
  - [ ] 6.1 Add POST route for order currency change
    - Add route to handle currency selection form submission
    - Redirect back to order show page with new currency
    - _Requirements: 1.5_
  - [ ] 6.2 Add POST route for vendor payment currency change
    - Add route to handle currency selection form submission
    - Redirect back to payment page with new currency
    - _Requirements: 2.2_

- [ ] 7. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [ ]* 8. Write remaining property tests
  - [ ]* 8.1 Write property test for currency preservation
    - **Property 5: Currency preservation through order lifecycle**
    - **Validates: Requirements 5.2**
  - [ ]* 8.2 Write property test for decimal precision
    - **Property 7: Decimal precision per currency**
    - **Validates: Requirements 4.2**

- [ ] 9. Final Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.
