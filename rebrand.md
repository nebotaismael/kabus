Based on the detailed transcripts, video analysis, and your previous files, here is the updated **Master Context Document** for Project Hecate.

This document integrates the technical backend requirements (NowPayments) with the **major UI/UX overhaul** requested in the voice notes to differentiate the site from "Inferno."

---

# 🤖 AI Agent Context Document: Project Hecate (Updated)

**Project Description:**
We are rebranding and restructuring the "Kabus" Monero marketplace into **"Hecate"**. The goal is to create a distinct visual identity that differs significantly from the default script (used by the competitor "Inferno").

**Core Constraints:**

* **No JavaScript:** All UI changes (dropdowns, tabs) must rely on CSS (`:hover`, `:focus`, checkbox hacks) or server-side rendering.
* **differentiation:** The layout must **not** look like the default Kabus script.

---

## 🎨 Phase 1: UI/UX Structural Overhaul (Priority High)

*References: Audio 7.22.15 PM, 7.22.16 PM, 7.22.17 PM, 7.22.18 PM*

### 1. Header & Top Bar Transformation

**Goal:** Create a taller, more feature-rich header to "overshadow" the competitor's design.

* **Files:** `resources/views/layouts/app.blade.php`, `public/css/styles.css`
* **Requirements:**
* **Dimensions:** Increase the height/padding of the Top Bar significantly.
* **Logo:** Position at the **far left edge**.
* **Main Navigation:** Move "Home", "Become a Vendor", "Messages", "Support", and "Orders" into the header (next to the logo). *Do not hide these in a sidebar.*
* **Icons (Cart & Notification):**
* **Remove Borders:** Remove the circular background/border around these icons. They should be free-floating icons.


* **Styling:** Ensure it doesn't look "plain." Use the new Hecate color palette (Dark Grey/Purple).



### 2. Sidebar Removal & User Menu

**Goal:** Widen the main content area to fit more products.

* **Files:** `resources/views/components/left-bar.blade.php`, `resources/views/components/navbar.blade.php`
* **Action:** **Remove the User Sidebar completely** (the left column containing Dashboard, Settings, Account, etc.).
* **Implementation:**
* Move these links (Dashboard, Settings, Account, Logout) into a **CSS-only Dropdown Menu** located under the User's Profile Name in the Header.
* *Reference:* See "Venom" and "Inferno" logic—user controls belong in a dropdown, not a side column.



### 3. Homepage Layout Restructuring

**Goal:** Mimic the "Inferno" density (3 rows) but with better organization.

* **Files:** `resources/views/home.blade.php`, `resources/views/components/products.blade.php`
* **Grid Layout:**
* With the sidebar removed, expand the **Featured Products** container to full width.
* Force the product grid to display **3 items per row** (CSS Grid/Flexbox).


* **Section Reordering:**
* **Top:** Header/Nav.
* **Middle (Left):** Add a *new* "Product Categories" list (since the competitor doesn't have this, it adds differentiation).
* **Middle (Center/Right):** Featured Products Grid.
* **Bottom:** Move the **"Recent Sales"** section to the very bottom of the page (just above the footer).



---

## 💳 Phase 2: NowPayments Integration (Backend)

*References: nowpay.md, Audio 7.17.24 PM*

### 1. Service Implementation

* **File:** `app/Services/NowPaymentsService.php`
* **Task:** Complete the `createPayment` and `getPaymentStatus` methods using the NowPayments API.
* **Logic:**
* Generate a deposit address (BTC/LTC/ETH) for the order.
* Store the `payment_id` in the `orders` table (ensure migration `2026_01_09` is run).



### 2. Webhook Handling

* **File:** `app/Http/Controllers/WebhookController.php`
* **Task:** Handle IPN callbacks.
* On `finished`: Mark order as `PAID`.
* On `partially_paid`: Notify user/admin (do not release goods).



---

## 🛠️ Phase 3: Specific Client Tweaks

*References: Audio 7.25.37 PM, 7.22.18 PM*

1. **Vendor Fee Config:**
* Add a field in the Admin Panel settings to adjust the "Become Vendor" fee (USD value) dynamically.


2. **Captcha:**
* File: `config/captcha.php`
* Action: Simplify the math problem difficulty or increase user tolerance.


3. **Vendor Dashboard:**
* Display "Total Sales" in **USD** (converted from XMR) alongside the XMR value.



---

## 📂 Critical File List for AI

When asking the AI to code, point it to these specific files first:

1. **Frontend/Layout:**
* `resources/views/layouts/app.blade.php` (Master layout)
* `resources/views/home.blade.php` (Homepage structure)
* `public/css/styles.css` (Global styles)
* `resources/views/components/navbar.blade.php` (Nav items)


2. **Backend/Logic:**
* `app/Services/NowPaymentsService.php`
* `routes/web.php`


3. **Config:**
* `config/marketplace.php` (Site settings)