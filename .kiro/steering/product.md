# Product Overview

Hecate Market (formerly Kabus) is a privacy-focused Monero marketplace built with Laravel 11 and PHP 8.3.

## Core Purpose

Anonymous e-commerce platform enabling vendors to sell products (digital, cargo, dead-drop) with cryptocurrency payments via NowPayments.io gateway.

## Key Features

- **Walletless Escrow:** No user wallets; payments are per-order and escrowed until resolution
- **Vendor System:** Registration fees, product management, sales tracking, payouts
- **Order Flow:** Cart → Checkout → Payment → Fulfillment → Review/Dispute
- **Security:** PGP-based 2FA, mnemonic recovery, no JavaScript
- **Admin Panel:** User management, disputes, categories, statistics, support tickets

## User Roles

- **Buyer:** Browse products, purchase, manage orders, open disputes
- **Vendor:** List products, manage sales, receive payouts, handle disputes
- **Admin:** Full platform control, dispute resolution, user moderation

## Payment Flow

All payments (orders, vendor fees, advertisements) use NowPayments.io API. Vendor payouts and refunds also go through NowPayments Payout API.
