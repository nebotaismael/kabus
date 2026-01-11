<nav class="navbar navbar-hecate">
    <div class="navbar-container">
        <div class="navbar-content">
            {{-- Logo at far left --}}
            <div class="navbar-left">
                <a href="{{ route('home') }}" class="navbar-logo-link">
                    <img src="{{ asset('images/hecate.png') }}" alt="Hecate" class="navbar-brand-logo">
                </a>
                
                {{-- Main Navigation Links --}}
                @auth
                <div class="navbar-main-nav">
                    <a href="{{ route('home') }}" class="navbar-nav-link {{ request()->routeIs('home') ? 'active' : '' }}">
                        <img src="{{ asset('icons/home.png') }}" alt="Home" class="navbar-nav-icon">
                        Home
                    </a>
                    <a href="{{ route('products.index') }}" class="navbar-nav-link {{ request()->routeIs('products.*') ? 'active' : '' }}">
                        <img src="{{ asset('icons/products.png') }}" alt="Products" class="navbar-nav-icon">
                        Products
                    </a>
                    <a href="{{ route('become.vendor') }}" class="navbar-nav-link {{ request()->routeIs('become.*') ? 'active' : '' }}">
                        <img src="{{ asset('icons/become-vendor.png') }}" alt="Become Vendor" class="navbar-nav-icon">
                        Become Vendor
                    </a>
                    <a href="{{ route('messages.index') }}" class="navbar-nav-link {{ request()->routeIs('messages.*') ? 'active' : '' }}">
                        <img src="{{ asset('icons/messages.png') }}" alt="Messages" class="navbar-nav-icon">
                        Messages
                    </a>
                    <a href="{{ route('support.index') }}" class="navbar-nav-link {{ request()->routeIs('support.*') ? 'active' : '' }}">
                        <img src="{{ asset('icons/support.png') }}" alt="Support" class="navbar-nav-icon">
                        Support
                    </a>
                    <a href="{{ route('orders.index') }}" class="navbar-nav-link {{ request()->routeIs('orders.*') ? 'active' : '' }}">
                        <img src="{{ asset('icons/orders.png') }}" alt="Orders" class="navbar-nav-icon">
                        Orders
                    </a>
                </div>
                @endauth
            </div>
            
            {{-- Right side: Icons and User Menu --}}
            <div class="navbar-right">
                @auth
                    {{-- Cart Icon (no border) --}}
                    <a href="{{ route('cart.index') }}" class="navbar-icon-flat {{ request()->routeIs('cart.*') ? 'active' : '' }}">
                        <img src="{{ asset('icons/cart.png') }}" alt="Cart" class="navbar-icon-png">
                        @if(auth()->user()->cartItems()->count() > 0)
                            <span class="navbar-badge-flat">{{ auth()->user()->cartItems()->count() }}</span>
                        @endif
                    </a>
                    
                    {{-- Notification Icon (no border) --}}
                    <a href="{{ route('notifications.index') }}" class="navbar-icon-flat {{ request()->routeIs('notifications.*') ? 'active' : '' }}">
                        <img src="{{ asset('icons/notifications.png') }}" alt="Notifications" class="navbar-icon-png">
                        @if(auth()->user()->unread_notifications_count > 0)
                            <span class="navbar-badge-flat">{{ auth()->user()->unread_notifications_count }}</span>
                        @endif
                    </a>
                    
                    {{-- User Dropdown Menu (CSS-only) --}}
                    <div class="navbar-user-dropdown">
                        <div class="navbar-user-trigger">
                            <img src="{{ auth()->user()->profile_picture_url ?? asset('icons/account.png') }}" alt="Profile" class="navbar-user-avatar">
                            <span class="navbar-user-name">{{ auth()->user()->username }}</span>
                            <span class="navbar-dropdown-arrow">▼</span>
                        </div>
                        <div class="navbar-dropdown-menu">
                            <a href="{{ route('dashboard') }}" class="navbar-dropdown-item">
                                <img src="{{ asset('icons/dashboard.png') }}" alt="Dashboard" class="navbar-dropdown-icon">
                                Dashboard
                            </a>
                            <a href="{{ route('settings') }}" class="navbar-dropdown-item">
                                <img src="{{ asset('icons/settings.png') }}" alt="Settings" class="navbar-dropdown-icon">
                                Settings
                            </a>
                            <a href="{{ route('profile') }}" class="navbar-dropdown-item">
                                <img src="{{ asset('icons/account.png') }}" alt="Account" class="navbar-dropdown-icon">
                                Account
                            </a>
                            <a href="{{ route('wishlist.index') }}" class="navbar-dropdown-item">
                                <img src="{{ asset('icons/wishlist.png') }}" alt="Wishlist" class="navbar-dropdown-icon">
                                Wishlist
                            </a>
                            @if(auth()->user()->isVendor())
                            <a href="{{ route('vendor.index') }}" class="navbar-dropdown-item">
                                <img src="{{ asset('icons/v-panel.png') }}" alt="Vendor Panel" class="navbar-dropdown-icon">
                                Vendor Panel
                            </a>
                            @endif
                            @if(auth()->user()->isAdmin())
                            <a href="{{ route('admin.index') }}" class="navbar-dropdown-item">
                                <img src="{{ asset('icons/a-panel.png') }}" alt="Admin Panel" class="navbar-dropdown-icon">
                                Admin Panel
                            </a>
                            @endif
                            <div class="navbar-dropdown-divider"></div>
                            <form action="{{ route('logout') }}" method="POST" class="navbar-logout-form">
                                @csrf
                                <button type="submit" class="navbar-dropdown-item navbar-dropdown-logout">
                                    <img src="{{ asset('icons/logout.png') }}" alt="Logout" class="navbar-dropdown-icon">
                                    Logout
                                </button>
                            </form>
                        </div>
                    </div>
                @endauth
            </div>
        </div>
    </div>
</nav>
