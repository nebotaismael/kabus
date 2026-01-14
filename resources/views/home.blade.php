@extends('layouts.app')

@section('content')

@if($popup)
<input type="checkbox" id="pop-up-toggle" checked>
<div class="pop-up-container">
    <div class="pop-up-card">
        <h2 class="pop-up-title">{{ $popup->title }}</h2>
        <div class="pop-up-content">{{ $popup->message }}</div>
        <div class="pop-up-button-container">
            <label for="pop-up-toggle" class="pop-up-close-btn">
                Acknowledge & Continue
            </label>
        </div>
    </div>
</div>
@endif

<div class="home-container home-container-hecate">
    <div class="home-layout">
        {{-- Categories Sidebar --}}
        <aside class="home-categories-sidebar">
            <h3 class="home-categories-title">Categories</h3>
            <ul class="home-categories-list">
                @foreach(\App\Models\Category::mainCategories() as $category)
                    <li class="home-category-item">
                        <a href="{{ route('products.index', ['category' => $category->id]) }}" class="home-category-link">
                            {{ $category->name }}
                        </a>
                        @if($category->children->count() > 0)
                            <ul class="home-subcategories-list">
                                @foreach($category->children as $subcategory)
                                    <li>
                                        <a href="{{ route('products.index', ['category' => $subcategory->id]) }}" class="home-subcategory-link">
                                            {{ $subcategory->name }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </li>
                @endforeach
            </ul>
            
            {{-- Quick Links --}}
            <div class="home-quick-links">
                <h4 class="home-quick-links-title">Quick Links</h4>
                <a href="{{ route('vendors.index') }}" class="home-quick-link">
                    <img src="{{ asset('icons/vendors.png') }}" alt="Vendors" class="home-quick-link-icon">
                    Browse Vendors
                </a>
                <a href="{{ route('references.index') }}" class="home-quick-link">
                    <img src="{{ asset('icons/references.png') }}" alt="References" class="home-quick-link-icon">
                    References
                </a>
                <a href="{{ route('disputes.index') }}" class="home-quick-link">
                    <img src="{{ asset('icons/disputes.png') }}" alt="Disputes" class="home-quick-link-icon">
                    Disputes
                </a>
                <a href="{{ route('rules') }}" class="home-quick-link">
                    <img src="{{ asset('icons/rules.png') }}" alt="Rules" class="home-quick-link-icon">
                    Rules
                </a>
                <a href="{{ route('guides.index') }}" class="home-quick-link">
                    <img src="{{ asset('icons/guides.png') }}" alt="Guides" class="home-quick-link-icon">
                    Guides
                </a>
            </div>
        </aside>
        
        {{-- Main Content Area --}}
        <div class="home-main-content">
            {{-- Top Vendors Section --}}
            @if($topVendors->isNotEmpty())
            <div class="home-top-vendors">
                <h2 class="home-section-title">🔥 Top Vendors 🔥</h2>
                <div class="home-vendors-grid">
                    @foreach($topVendors as $vendor)
                        <a href="{{ route('vendors.show', $vendor->username) }}" class="home-vendor-card">
                            <div class="home-vendor-avatar">
                                <img src="{{ $vendor->profile?->profile_picture_url ?? asset('images/default-profile-picture.png') }}" 
                                     alt="{{ $vendor->username }}">
                            </div>
                            <div class="home-vendor-info">
                                <span class="home-vendor-username">{{ $vendor->username }}</span>
                                <span class="home-vendor-level">Level {{ $vendor->level }}</span>
                                <span class="home-vendor-deals">DEALS {{ $vendor->deals_count }}</span>
                                <span class="home-vendor-rating">
                                    RATING {{ $vendor->rating ? number_format($vendor->rating, 2) . '★' : '—' }}
                                </span>
                            </div>
                        </a>
                    @endforeach
                </div>
                <a href="{{ route('vendors.index') }}" class="home-all-vendors-link">▼ All Vendors ▼</a>
            </div>
            @endif

            {{-- Search Bar Section --}}
            <div class="home-search-section">
                <form action="{{ route('products.index') }}" method="GET" class="home-search-form">
                    <div class="home-search-filters">
                        <input type="text" name="vendor" placeholder="Search vendor 🔎" class="home-search-input">
                        <select name="type" class="home-search-select">
                            <option value="">All Types</option>
                            <option value="digital">Digital</option>
                            <option value="cargo">Cargo</option>
                            <option value="deaddrop">Dead Drop</option>
                        </select>
                        <select name="category" class="home-search-select">
                            <option value="">All Categories</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                        <select name="sort_price" class="home-search-select">
                            <option value="">Most Recent</option>
                            <option value="asc">Price: Low to High</option>
                            <option value="desc">Price: High to Low</option>
                        </select>
                    </div>
                    <div class="home-search-main">
                        <input type="text" name="search" placeholder="Search by product title 🔎" class="home-search-main-input">
                    </div>
                    <div class="home-search-actions">
                        <a href="{{ route('home') }}" class="home-search-reset">Reset Filters</a>
                        <button type="submit" class="home-search-submit">Apply Filters</button>
                    </div>
                </form>
            </div>

            @if(count($adSlots) > 0)
                <div class="home-highlight-title-wrapper">
                    <h2 class="home-highlight-heading">Advertised Products</h2>
                </div>
                <div class="home-highlight-container home-highlight-grid-3">
                    @for($i = 1; $i <= 8; $i++)
                        @if(isset($adSlots[$i]))
                            <div class="home-highlight-card">
                                {{-- Product Image --}}
                                <div class="home-highlight-image">
                                    <img src="{{ $adSlots[$i]['product']->product_picture_url }}" 
                                         alt="{{ $adSlots[$i]['product']->name }}">
                                </div>

                                {{-- Product Details --}}
                                <div class="home-highlight-content">
                                    <div class="home-highlight-header">
                                        <div class="home-highlight-title-section">
                                            <h3 class="home-highlight-title">{{ $adSlots[$i]['product']->name }}</h3>
                                            <div class="home-highlight-badges">
                                            <span class="home-highlight-type home-highlight-type-{{ $adSlots[$i]['product']->type }}">
                                                {{ ucfirst($adSlots[$i]['product']->type) }}
                                            </span>
                                                <span class="home-highlight-vendor">
                                                    <a href="{{ route('vendors.show', ['username' => $adSlots[$i]['vendor']->username]) }}" class="home-highlight-vendor-link">
                                                        {{ $adSlots[$i]['vendor']->username }}
                                                    </a>
                                                </span>
                                                <span class="home-highlight-badge home-highlight-category">
                                                    {{ $adSlots[$i]['product']->category->name }}
                                                </span>
                                            </div>
                                        </div>
                                        
                                        <div class="home-highlight-price-section">
                                            <div class="home-highlight-price">
                                                ${{ number_format($adSlots[$i]['product']->price, 2) }}
                                            </div>
                                            @if($adSlots[$i]['xmr_price'] !== null)
                                                <div class="home-highlight-xmr">
                                                    ≈ ɱ{{ number_format($adSlots[$i]['xmr_price'], 4) }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="home-highlight-details">
                                        <div class="home-highlight-info">
                                            <div class="home-highlight-stock">
                                                {{ number_format($adSlots[$i]['product']->stock_amount) }} 
                                                {{ $adSlots[$i]['measurement_unit'] }}
                                            </div>

                                            <div class="home-highlight-shipping">
                                                {{ $adSlots[$i]['product']->ships_from }} ➜ {{ $adSlots[$i]['product']->ships_to }}
                                            </div>

                                            @if(!empty($adSlots[$i]['bulk_options']))
                                                <div class="home-highlight-bulk-preview">
                                                    {{ count($adSlots[$i]['bulk_options']) }} Bulk Offers
                                                </div>
                                            @endif

                                                <div class="home-highlight-delivery-preview">
                                                    {{ count($adSlots[$i]['delivery_options']) }} Delivery Options
                                                </div>
                                        </div>

                                        <div class="home-highlight-action">
                                            <a href="{{ route('products.show', $adSlots[$i]['product']) }}" 
                                               class="home-highlight-button">
                                                View Product
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endfor
                </div>
            @endif

            @if(count($featuredProducts) > 0)
                <div class="home-highlight-title-wrapper">
                    <h2 class="home-highlight-heading">Featured Products</h2>
                </div>
                <div class="home-highlight-container home-highlight-grid-3">
                    @foreach($featuredProducts as $featured)
                        <div class="home-highlight-card">
                            {{-- Product Image --}}
                            <div class="home-highlight-image">
                                <img src="{{ $featured['product']->product_picture_url }}" 
                                     alt="{{ $featured['product']->name }}">
                            </div>

                            {{-- Product Details --}}
                            <div class="home-highlight-content">
                                <div class="home-highlight-header">
                                    <div class="home-highlight-title-section">
                                        <h3 class="home-highlight-title">{{ $featured['product']->name }}</h3>
                                        <div class="home-highlight-badges">
                                        <span class="home-highlight-type home-highlight-type-{{ $featured['product']->type }}">
                                            {{ ucfirst($featured['product']->type) }}
                                        </span>
                                            <span class="home-highlight-vendor">
                                                <a href="{{ route('vendors.show', ['username' => $featured['vendor']->username]) }}" class="home-highlight-vendor-link">
                                                    {{ $featured['vendor']->username }}
                                                </a>
                                            </span>
                                            <span class="home-highlight-badge home-highlight-category">
                                                {{ $featured['product']->category->name }}
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <div class="home-highlight-price-section">
                                        <div class="home-highlight-price">
                                            ${{ number_format($featured['product']->price, 2) }}
                                        </div>
                                        @if($featured['xmr_price'] !== null)
                                            <div class="home-highlight-xmr">
                                                ≈ ɱ{{ number_format($featured['xmr_price'], 4) }}
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <div class="home-highlight-details">
                                    <div class="home-highlight-info">
                                        <div class="home-highlight-stock">
                                            {{ number_format($featured['product']->stock_amount) }} 
                                            {{ $featured['measurement_unit'] }}
                                        </div>

                                        <div class="home-highlight-shipping">
                                            {{ $featured['product']->ships_from }} ➜ {{ $featured['product']->ships_to }}
                                        </div>

                                        @if(!empty($featured['bulk_options']))
                                            <div class="home-highlight-bulk-preview">
                                                {{ count($featured['bulk_options']) }} Bulk Offers
                                            </div>
                                        @endif

                                            <div class="home-highlight-delivery-preview">
                                                {{ count($featured['delivery_options']) }} Delivery Options
                                            </div>
                                    </div>

                                    <div class="home-highlight-action">
                                        <a href="{{ route('products.show', $featured['product']) }}" 
                                           class="home-highlight-button">
                                            View Product
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            @if(count($adSlots) === 0 && count($featuredProducts) === 0)
            <div class="home-welcome-message">
                <h1 class="home-title">Welcome to {{ config('app.name') }}</h1>
            
                <p class="home-text">Dear users,</p>
            
                <p class="home-text">
                    I am excited to announce the official release of {{ config('app.name') }}! Our marketplace is now fully functional and ready for trading.
                </p>
            
                <p class="home-text">
                    Thank you for your valuable feedback during the beta phase. It has helped me enhance the platform to better meet your needs.
                </p>
            
                <p class="home-text">What's Next:</p>
            
                <ul class="home-list">
                    <li>Continuous updates and new feature integrations</li>
                    <li>Your contributions and suggestions are always welcome</li>
                </ul>
            
                <div class="home-important">
                    <strong>Security Reminder</strong>
                    <p class="home-text">
                    Please use this marketplace script with caution. Despite my best efforts, there might be unfound vulnerabilities. I recommend that you do not use this script directly; instead, review and edit it according to your needs. Remember the most important rule of the internet: don't trust, verify.
                    </p>
                </div>
            
                <p class="home-text">
                    We look forward to growing and evolving together with you. Stay tuned for more updates!
                </p>
            
                <div class="home-signature">
                    <p>Best regards,<br>sukunetsiz</p>
                </div>
            </div>
            @endif
        </div>
    </div>
    
    {{-- Recent Purchases Section --}}
    @if($recentPurchases->isNotEmpty())
    <div class="home-recent-purchases">
        <h2 class="home-section-title">Recent Purchases</h2>
        <div class="home-purchases-grid">
            @foreach($recentPurchases as $purchase)
                @if($purchase['product_slug'])
                <a href="{{ route('products.show', $purchase['product_slug']) }}" class="home-purchase-item home-purchase-link">
                @else
                <div class="home-purchase-item">
                @endif
                    <div class="home-purchase-image">
                        <img src="{{ $purchase['product_picture_url'] }}" alt="{{ $purchase['product_name'] }}">
                    </div>
                    <div class="home-purchase-info">
                        <span class="home-purchase-product">{{ $purchase['product_name'] }}</span>
                        <span class="home-purchase-price">${{ number_format($purchase['price'], 2) }}</span>
                    </div>
                @if($purchase['product_slug'])
                </a>
                @else
                </div>
                @endif
            @endforeach
        </div>
    </div>
    @endif
</div>
@endsection
