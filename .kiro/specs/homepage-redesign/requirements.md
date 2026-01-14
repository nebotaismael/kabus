# Requirements Document

## Introduction

This document specifies the requirements for redesigning the Hecate Market homepage to transform it from a static welcome page into a dynamic, product-focused landing page. The new homepage will showcase top vendors, provide search functionality, display featured products, and show recent purchases to create an engaging marketplace experience.

## Glossary

- **Homepage**: The main landing page displayed to authenticated users at the root URL
- **Top_Vendors_Section**: A section displaying the highest-rated and most active vendors with their profile pictures, levels, deals count, and ratings
- **Search_Bar**: The product search interface with filters for vendor, product type, category, and price sorting
- **Featured_Products_Section**: A section displaying products that have been marked as featured by vendors
- **Recent_Purchases_Section**: A section displaying recently completed orders to show marketplace activity
- **Vendor_Card**: A UI component displaying vendor information including profile picture, username, level, deals count, and rating
- **Product_Card**: A UI component displaying product information including image, name, vendor, category, price, and shipping details
- **Vendor_Level**: A numeric indicator (1-20+) representing vendor experience based on completed deals
- **Deals_Count**: The total number of completed orders for a vendor
- **Vendor_Rating**: The average rating (0-5 stars) calculated from product reviews

## Requirements

### Requirement 1: Top Vendors Section Display

**User Story:** As a buyer, I want to see top vendors prominently on the homepage, so that I can quickly find reputable sellers.

#### Acceptance Criteria

1. WHEN the Homepage loads, THE Top_Vendors_Section SHALL display at the top of the main content area
2. THE Top_Vendors_Section SHALL display a maximum of 5 Vendor_Cards in a responsive grid layout
3. WHEN displaying vendors, THE Homepage SHALL order them by Vendor_Level descending, then by Deals_Count descending
4. THE Vendor_Card SHALL display the vendor's profile picture, username, level, deals count, and rating
5. WHEN a vendor has no reviews, THE Vendor_Card SHALL display "—" for the rating value
6. WHEN a user clicks on a Vendor_Card, THE Homepage SHALL navigate to that vendor's profile page
7. THE Top_Vendors_Section SHALL include an "All Vendors" link that navigates to the vendors listing page
8. WHILE a vendor is in vacation mode, THE Top_Vendors_Section SHALL exclude that vendor from display

### Requirement 2: Search Bar Integration

**User Story:** As a buyer, I want to search for products directly from the homepage, so that I can quickly find items I'm looking for.

#### Acceptance Criteria

1. WHEN the Homepage loads, THE Search_Bar SHALL display below the Top_Vendors_Section
2. THE Search_Bar SHALL include a vendor name filter input field
3. THE Search_Bar SHALL include a product type dropdown with options: All Types, Digital, Cargo, Dead Drop
4. THE Search_Bar SHALL include a category dropdown populated with all active categories
5. THE Search_Bar SHALL include a price sort dropdown with options: Most Recent, Price Low to High, Price High to Low
6. THE Search_Bar SHALL include a main product search input field
7. WHEN a user submits the search form, THE Homepage SHALL redirect to the products page with the selected filters applied
8. THE Search_Bar SHALL include a "Reset Filters" button that clears all filter selections
9. THE Search_Bar SHALL include an "Apply Filters" button that submits the search

### Requirement 3: Featured Products Section Display

**User Story:** As a buyer, I want to see featured products on the homepage, so that I can discover promoted items from vendors.

#### Acceptance Criteria

1. WHEN the Homepage loads, THE Featured_Products_Section SHALL display below the Search_Bar
2. THE Featured_Products_Section SHALL display Product_Cards in a responsive grid layout
3. WHEN no featured products exist, THE Featured_Products_Section SHALL not be displayed
4. THE Product_Card SHALL display the product image, name, type badge, vendor name, category, price in USD, and price in XMR
5. THE Product_Card SHALL display shipping information showing origin and destination
6. THE Product_Card SHALL include a "View Product" button linking to the product detail page
7. WHILE a product is soft-deleted, THE Featured_Products_Section SHALL exclude that product from display
8. WHILE a vendor is in vacation mode, THE Featured_Products_Section SHALL exclude products from that vendor

### Requirement 4: Recent Purchases Section Display

**User Story:** As a buyer, I want to see recent purchases on the homepage, so that I can see marketplace activity and popular items.

#### Acceptance Criteria

1. WHEN the Homepage loads, THE Recent_Purchases_Section SHALL display at the bottom of the main content area
2. THE Recent_Purchases_Section SHALL display a maximum of 8 recent completed orders
3. WHEN displaying purchases, THE Homepage SHALL order them by completion date descending (most recent first)
4. THE Recent_Purchases_Section SHALL display the product name and price for each purchase
5. WHEN no completed orders exist, THE Recent_Purchases_Section SHALL not be displayed
6. THE Recent_Purchases_Section SHALL only include orders with status "completed"

### Requirement 5: Layout Structure

**User Story:** As a buyer, I want a clean and organized homepage layout, so that I can easily navigate and find information.

#### Acceptance Criteria

1. THE Homepage SHALL maintain the existing categories sidebar on the left side
2. THE Homepage SHALL maintain the existing quick links section in the sidebar
3. THE Homepage SHALL display sections in this order: Top Vendors, Search Bar, Featured Products, Recent Purchases
4. WHEN the popup is active, THE Homepage SHALL display the popup overlay before showing the main content
5. THE Homepage SHALL remove the static welcome message when featured products or advertisements exist
6. THE Homepage SHALL use consistent styling with the existing marketplace design

### Requirement 6: Vendor Level Calculation

**User Story:** As a system administrator, I want vendor levels calculated based on completed deals, so that buyers can assess vendor experience.

#### Acceptance Criteria

1. THE Homepage SHALL calculate Vendor_Level based on the number of completed orders
2. WHEN a vendor has 0-9 completed orders, THE Vendor_Level SHALL be 1
3. WHEN a vendor has 10-24 completed orders, THE Vendor_Level SHALL be 2
4. WHEN a vendor has 25-49 completed orders, THE Vendor_Level SHALL be 3
5. WHEN a vendor has 50-99 completed orders, THE Vendor_Level SHALL be 4
6. WHEN a vendor has 100-199 completed orders, THE Vendor_Level SHALL be 5
7. FOR EACH additional 100 completed orders above 200, THE Vendor_Level SHALL increase by 1 up to a maximum of 20

### Requirement 7: Vendor Rating Calculation

**User Story:** As a buyer, I want to see accurate vendor ratings, so that I can make informed purchasing decisions.

#### Acceptance Criteria

1. THE Homepage SHALL calculate Vendor_Rating as the average of all product review ratings for that vendor
2. WHEN calculating the rating, THE Homepage SHALL use a 5-star scale with decimal precision to 2 places
3. WHEN a vendor has no reviews, THE Vendor_Rating SHALL be displayed as "—" (em dash)
4. THE Vendor_Rating SHALL be calculated from all reviews across all of the vendor's products

### Requirement 8: Visual Consistency with Product Pages

**User Story:** As a buyer, I want the homepage to have consistent visual styling with the product pages, so that I have a cohesive browsing experience.

#### Acceptance Criteria

1. THE Homepage SHALL use the same color scheme as the product detail page (#1e1e1e backgrounds, #292929 cards, #534d4d accents)
2. THE Homepage product cards SHALL use consistent badge styling with the product detail page (type badges, category badges)
3. THE Homepage sections SHALL have proper border-radius (8px-12px) matching the product page styling
4. THE Homepage text colors SHALL match the product page (#e0e0e0 for primary text, #a0a0a0 for secondary text, #534d4d for accents)
5. THE Homepage buttons SHALL use consistent styling with the product page (padding, border-radius, hover effects)

### Requirement 9: Layout Overflow Prevention

**User Story:** As a buyer, I want the homepage to display properly without content overflow, so that I can view all information clearly.

#### Acceptance Criteria

1. THE Homepage product cards SHALL contain all content within their boundaries without overflow
2. THE Homepage grid layouts SHALL properly wrap content on smaller screens
3. WHEN product names are long, THE Homepage SHALL truncate them with ellipsis rather than overflow
4. THE Homepage featured products section SHALL use a proper grid layout that prevents horizontal overflow
5. THE Homepage vendor cards SHALL maintain consistent sizing regardless of content length
6. THE Homepage search bar inputs SHALL not overflow their container boundaries

### Requirement 10: Product Card Visual Enhancement

**User Story:** As a buyer, I want product cards on the homepage to be visually appealing and informative, so that I can quickly assess products.

#### Acceptance Criteria

1. THE Homepage product cards SHALL display product images with consistent aspect ratios
2. THE Homepage product cards SHALL have clear visual hierarchy (image, title, price, details)
3. THE Homepage product cards SHALL have hover effects that provide visual feedback
4. THE Homepage product cards SHALL display badges (type, category) with proper spacing and alignment
5. THE Homepage product cards SHALL use box-shadow effects consistent with the product page styling
6. WHEN displaying prices, THE Homepage SHALL show both USD and XMR prices with proper formatting
