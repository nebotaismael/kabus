<div class="right-bar">
    <ul>
        @php
            $menuItems = [
                ['route' => 'dashboard', 'routeIs' => 'dashboard', 'icon' => 'dashboard.png', 'alt' => 'Dashboard', 'label' => 'Dashboard'],
                ['route' => 'settings', 'routeIs' => 'settings', 'icon' => 'settings.png', 'alt' => 'Settings', 'label' => 'Settings'],
                ['route' => 'profile', 'routeIs' => 'profile', 'icon' => 'account.png', 'alt' => 'Account', 'label' => 'Account'],
                ['route' => 'support.index', 'routeIs' => 'support.*', 'icon' => 'support.png', 'alt' => 'Support', 'label' => 'Support'],
                ['route' => 'messages.index', 'routeIs' => 'messages.*', 'icon' => 'messages.png', 'alt' => 'Messages', 'label' => 'Messages'],
                ['route' => 'rules', 'routeIs' => 'rules', 'icon' => 'rules.png', 'alt' => 'Rules', 'label' => 'Rules'],
                ['route' => 'guides.index', 'routeIs' => 'guides.*', 'icon' => 'guides.png', 'alt' => 'Guides', 'label' => 'Guides'],
            ];
            shuffle($menuItems);
        @endphp

        @foreach($menuItems as $item)
            <li>
                <a href="{{ route($item['route']) }}" class="{{ request()->routeIs($item['routeIs']) ? 'active' : '' }}">
                    {{ $item['label'] }}
                    <img src="{{ asset('icons/' . $item['icon']) }}" alt="{{ $item['alt'] }}" class="left-bar-right-bar-icon right-bar-icon">
                </a>
            </li>
        @endforeach

        @if(auth()->user()->isVendor())
        <li>
            <a href="{{ route('vendor.index') }}" class="{{ request()->routeIs('vendor.*') ? 'active' : '' }}">
                V-Panel
                <img src="{{ asset('icons/v-panel.png') }}" alt="Vendor Panel" class="left-bar-right-bar-icon right-bar-icon">
            </a>
        </li>
        @endif
    </ul>
</div>
