<div class="left-bar">
    <ul>
        @php
            $menuItems = [
                ['route' => 'wishlist.index', 'routeIs' => 'wishlist.*', 'icon' => 'wishlist.png', 'alt' => 'Wishlist', 'label' => 'Wishlist'],
                ['route' => 'products.index', 'routeIs' => 'products.*', 'icon' => 'products.png', 'alt' => 'Products', 'label' => 'Products'],
                ['route' => 'orders.index', 'routeIs' => 'orders.*', 'icon' => 'orders.png', 'alt' => 'Orders', 'label' => 'Orders'],
                ['route' => 'return-addresses.index', 'routeIs' => 'return-addresses.*', 'icon' => 'return-addresses.png', 'alt' => 'Addresses', 'label' => 'Addresses'],
                ['route' => 'vendors.index', 'routeIs' => 'vendors.*', 'icon' => 'vendors.png', 'alt' => 'Vendors', 'label' => 'Vendors'],
                ['route' => 'become.vendor', 'routeIs' => 'become.*', 'icon' => 'become-vendor.png', 'alt' => 'Become Vendor', 'label' => 'Be a Vendor'],
                ['route' => 'references.index', 'routeIs' => 'references.*', 'icon' => 'references.png', 'alt' => 'References', 'label' => 'References'],
                ['route' => 'disputes.index', 'routeIs' => 'disputes.*', 'icon' => 'disputes.png', 'alt' => 'Disputes', 'label' => 'Disputes'],
            ];
            shuffle($menuItems);
        @endphp

        @foreach($menuItems as $item)
            <li>
                <a href="{{ route($item['route']) }}" class="{{ request()->routeIs($item['routeIs']) ? 'active' : '' }}">
                    <img src="{{ asset('icons/' . $item['icon']) }}" alt="{{ $item['alt'] }}" class="left-bar-right-bar-icon left-bar-icon">
                    {{ $item['label'] }}
                </a>
            </li>
        @endforeach

        @if(auth()->user()->isAdmin())
        <li>
            <a href="{{ route('admin.index') }}" class="{{ request()->routeIs('admin.*') ? 'active' : '' }}">
                <img src="{{ asset('icons/a-panel.png') }}" alt="Admin Panel" class="left-bar-right-bar-icon left-bar-icon">
                A-Panel
            </a>
        </li>
        @endif
    </ul>
</div>
