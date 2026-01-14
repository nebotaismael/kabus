# Implementation Plan: Homepage Redesign

## Overview

This implementation plan transforms the Hecate Market homepage into a dynamic, product-focused landing page with Top Vendors, Search Bar, Featured Products, and Recent Purchases sections. The implementation follows an incremental approach, building helper methods first, then controller logic, and finally the view components.

## Tasks

- [x] 1. Add vendor statistics helper methods to HomeController
  - [x] 1.1 Implement `calculateVendorLevel` static method
    - Add method that takes completed deals count and returns level 1-20
    - Implement threshold logic: 0-9→1, 10-24→2, 25-49→3, 50-99→4, 100-199→5, 200+→6-20
    - Cap maximum level at 20
    - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5, 6.6, 6.7_
  - [ ]* 1.2 Write property test for vendor level calculation
    - **Property 1: Vendor Level Calculation Correctness**
    - **Validates: Requirements 6.1, 6.2, 6.3, 6.4, 6.5, 6.6, 6.7**
  - [x] 1.3 Implement `calculateVendorRating` static method
    - Add method that takes vendor ID and returns average rating or null
    - Query all products for vendor, then average all review ratings
    - Round result to 2 decimal places
    - Return null if no reviews exist
    - _Requirements: 7.1, 7.2, 7.3, 7.4_
  - [ ]* 1.4 Write property test for vendor rating calculation
    - **Property 2: Vendor Rating Calculation Correctness**
    - **Validates: Requirements 7.1, 7.2, 7.4**

- [x] 2. Implement `getTopVendors` method in HomeController
  - [x] 2.1 Create private `getTopVendors` method
    - Query users with vendor role
    - Exclude vendors in vacation mode
    - Load profile relationship for profile pictures
    - Count completed orders per vendor
    - Calculate level and rating for each vendor
    - Sort by level descending, then deals count descending
    - Limit to 5 vendors
    - _Requirements: 1.2, 1.3, 1.8_
  - [ ]* 2.2 Write property test for top vendors sorting
    - **Property 3: Top Vendors Sorting Correctness**
    - **Validates: Requirements 1.3**
  - [ ]* 2.3 Write property test for top vendors maximum count
    - **Property 4: Top Vendors Maximum Count**
    - **Validates: Requirements 1.2**
  - [ ]* 2.4 Write property test for vacation mode filtering
    - **Property 7: Vacation Mode Vendor Filtering**
    - **Validates: Requirements 1.8, 3.8**

- [x] 3. Implement `getRecentPurchases` method in HomeController
  - [x] 3.1 Create private `getRecentPurchases` method
    - Query orders with status "completed"
    - Order by completed_at descending
    - Limit to 8 orders
    - Load order items relationship
    - Map to product name and price
    - _Requirements: 4.2, 4.3, 4.6_
  - [ ]* 3.2 Write property test for recent purchases sorting
    - **Property 5: Recent Purchases Sorting Correctness**
    - **Validates: Requirements 4.3**
  - [ ]* 3.3 Write property test for recent purchases maximum count
    - **Property 6: Recent Purchases Maximum Count**
    - **Validates: Requirements 4.2**
  - [ ]* 3.4 Write property test for completed orders only filtering
    - **Property 9: Completed Orders Only Filtering**
    - **Validates: Requirements 4.6**

- [x] 4. Update HomeController index method
  - [x] 4.1 Modify `index` method to call new helper methods
    - Call `getTopVendors()` and pass to view
    - Call `getRecentPurchases()` and pass to view
    - Fetch main categories for search bar dropdown
    - Pass all data to view
    - _Requirements: 1.1, 2.1, 3.1, 4.1, 5.3_
  - [ ]* 4.2 Write property test for categories dropdown completeness
    - **Property 10: Categories Dropdown Completeness**
    - **Validates: Requirements 2.4**

- [x] 5. Checkpoint - Ensure all controller tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [x] 6. Create Top Vendors section in home.blade.php
  - [x] 6.1 Add Top Vendors section HTML structure
    - Add section with title "🔥 Top Vendors 🔥"
    - Create responsive grid for vendor cards
    - Display vendor profile picture, username, level, deals, rating
    - Link each card to vendor profile page
    - Add "All Vendors" link at bottom
    - Handle empty state (no vendors)
    - _Requirements: 1.1, 1.4, 1.5, 1.6, 1.7_

- [x] 7. Create Search Bar section in home.blade.php
  - [x] 7.1 Add Search Bar section HTML structure
    - Add form with action to products.index route
    - Add vendor name input field
    - Add product type dropdown (All Types, Digital, Cargo, Dead Drop)
    - Add category dropdown populated from $categories
    - Add price sort dropdown (Most Recent, Low to High, High to Low)
    - Add main product search input
    - Add Reset Filters link and Apply Filters button
    - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 2.6, 2.7, 2.8, 2.9_

- [x] 8. Update Featured Products section in home.blade.php
  - [x] 8.1 Modify existing Featured Products section
    - Ensure section displays below search bar
    - Verify product cards show all required data
    - Ensure soft-deleted products are excluded (handled in controller)
    - Ensure vacation mode vendor products excluded (handled in controller)
    - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 3.7, 3.8_
  - [ ]* 8.2 Write property test for soft-deleted product filtering
    - **Property 8: Soft-Deleted Product Filtering**
    - **Validates: Requirements 3.7**

- [x] 9. Create Recent Purchases section in home.blade.php
  - [x] 9.1 Add Recent Purchases section HTML structure
    - Add section at bottom of main content
    - Display product name and price for each purchase
    - Handle empty state (no purchases)
    - _Requirements: 4.1, 4.4, 4.5_

- [x] 10. Add CSS styles for new homepage sections
  - [x] 10.1 Add styles for Top Vendors section
    - Style vendor cards with profile picture, info layout
    - Add hover effects for cards
    - Style "All Vendors" link
    - Ensure responsive grid layout
    - _Requirements: 1.4, 5.6_
  - [x] 10.2 Add styles for Search Bar section
    - Style form inputs and dropdowns
    - Style action buttons
    - Ensure consistent styling with existing marketplace
    - _Requirements: 2.1, 5.6_
  - [x] 10.3 Add styles for Recent Purchases section
    - Style purchase items
    - Ensure consistent styling with existing marketplace
    - _Requirements: 4.4, 5.6_

- [x] 11. Update layout and section ordering
  - [x] 11.1 Reorganize home.blade.php section order
    - Ensure Top Vendors is first in main content
    - Ensure Search Bar is second
    - Ensure Featured Products is third
    - Ensure Recent Purchases is at bottom
    - Maintain sidebar with categories and quick links
    - Remove static welcome message when content exists
    - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5_

- [ ] 12. Fix visual styling and overflow issues
  - [ ] 12.1 Fix product card overflow and layout issues
    - Add overflow: hidden to card containers
    - Add min-width: 0 to flex children for text truncation
    - Add text-overflow: ellipsis for long product names
    - Ensure images use object-fit: cover
    - _Requirements: 9.1, 9.3, 10.1_
  - [ ] 12.2 Update color scheme to match product page
    - Update background colors (#1e1e1e, #292929)
    - Update border colors (#3c3c3c, #534d4d)
    - Update text colors (#e0e0e0, #a0a0a0, #534d4d)
    - Ensure badge colors match product page styling
    - _Requirements: 8.1, 8.2, 8.4_
  - [ ] 12.3 Fix featured products grid layout
    - Implement proper 3-column grid with responsive breakpoints
    - Fix horizontal overflow issues
    - Ensure cards have consistent sizing
    - _Requirements: 9.2, 9.4, 9.5_
  - [ ] 12.4 Enhance product card visual hierarchy
    - Add proper box-shadow effects
    - Improve hover states
    - Ensure proper spacing and alignment for badges
    - _Requirements: 8.3, 8.5, 10.2, 10.3, 10.4, 10.5_
  - [ ] 12.5 Fix search bar input overflow
    - Ensure inputs don't overflow container
    - Add proper box-sizing
    - _Requirements: 9.6_

- [ ] 13. Final checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation
- Property tests validate universal correctness properties
- Unit tests validate specific examples and edge cases
- The existing Featured Products and Advertised Products sections are preserved and enhanced
