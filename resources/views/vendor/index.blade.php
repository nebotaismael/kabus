@extends('layouts.app')
@section('content')
<div class="a-v-panel-container">
    <div class="a-v-panel-card">
        <h1 class="a-v-panel-title">Hecate Merchant Command</h1>
        <p class="a-v-panel-welcome">Welcome to the Merchant Command Center. Here you can manage your products in {{ config('app.name') }}.</p>
        
        @php
            $userAddresses = auth()->user()->returnAddresses;
            $hasPayoutAddress = $userAddresses->isNotEmpty();
            $supportedCurrencies = app(\App\Services\NowPaymentsService::class)->getSupportedCurrencies();
            $userCurrencies = $userAddresses->pluck('currency')->unique()->toArray();
            $missingCurrencies = array_diff(array_keys($supportedCurrencies), $userCurrencies);
        @endphp
        
        @if(!$hasPayoutAddress)
            <div class="vendor-wallet-warning">
                <img src="{{ asset('images/information.png') }}" alt="Warning" class="vendor-wallet-warning-icon">
                <div class="vendor-wallet-warning-content">
                    <h4 class="vendor-wallet-warning-heading">Payout Wallet Required!</h4>
                    <p class="vendor-wallet-warning-text">You must add at least one cryptocurrency wallet address to receive payments from completed orders. Without a payout address, you will not be able to receive funds when buyers mark orders as complete.</p>
                    <a href="{{ route('return-addresses.index') }}" class="vendor-wallet-warning-link">Add Payout Wallet Now</a>
                </div>
            </div>
        @elseif(count($missingCurrencies) > 0)
            <div class="vendor-wallet-info">
                <img src="{{ asset('images/information.png') }}" alt="Info" class="vendor-wallet-info-icon">
                <div class="vendor-wallet-info-content">
                    <h4 class="vendor-wallet-info-heading">Add More Payout Addresses</h4>
                    <p class="vendor-wallet-info-text">
                        You're missing payout addresses for: 
                        @foreach($missingCurrencies as $code)
                            <strong>{{ $supportedCurrencies[$code]['symbol'] }}</strong>@if(!$loop->last), @endif
                        @endforeach. 
                        Buyers can pay with any supported cryptocurrency. Add addresses for all currencies to ensure you receive payments.
                    </p>
                    <a href="{{ route('return-addresses.index') }}" class="vendor-wallet-info-link">Manage Payout Addresses</a>
                </div>
            </div>
        @endif
        
        <div class="a-v-panel-grid">
            <!-- Product Management Section -->
            <div class="hecate-product-management-section">
                <h3>Product Management</h3>
                <div class="hecate-product-actions">
                    <div class="a-v-panel-item">
                        <h3 class="a-v-panel-item-title">Add Digital Product</h3>
                        <p class="a-v-panel-item-description">You can add digital products to {{ config('app.name') }}.</p>
                        <a href="{{ route('vendor.products.create', 'digital') }}" class="a-v-panel-item-link">Add Digital Product</a>
                    </div>
                    
                    <div class="a-v-panel-item">
                        <h3 class="a-v-panel-item-title">Add Cargo Product</h3>
                        <p class="a-v-panel-item-description">You can add physical products that can be delivered by shipping.</p>
                        <a href="{{ route('vendor.products.create', 'cargo') }}" class="a-v-panel-item-link">Add Cargo Product</a>
                    </div>
                    
                    <div class="a-v-panel-item">
                        <h3 class="a-v-panel-item-title">Add Dead Drop Product</h3>
                        <p class="a-v-panel-item-description">You can add products that can be delivered via dead drop.</p>
                        <a href="{{ route('vendor.products.create', 'deaddrop') }}" class="a-v-panel-item-link">Add Dead Drop Product</a>
                    </div>
                </div>
            </div>

            <div class="a-v-panel-item">
                <h3 class="a-v-panel-item-title">Vendor Appearance</h3>
                <p class="a-v-panel-item-description">You can customize your store appearance and profile.</p>
                <a href="{{ route('vendor.appearance') }}" class="a-v-panel-item-link">Edit Appearance</a>
            </div>
            
            <div class="a-v-panel-item">
                <h3 class="a-v-panel-item-title">My Products</h3>
                <p class="a-v-panel-item-description">You can view all products you have listed for sale on {{ config('app.name') }}.</p>
                <a href="{{ route('vendor.my-products') }}" class="a-v-panel-item-link">View My Products</a>
            </div>
            
            <div class="a-v-panel-item">
                <h3 class="a-v-panel-item-title">My Sales</h3>
                <p class="a-v-panel-item-description">You can view all your completed sales.</p>
                <a href="{{ route('vendor.sales') }}" class="a-v-panel-item-link">View My Sales</a>
            </div>

            <div class="a-v-panel-item">
                <h3 class="a-v-panel-item-title">My Disputes</h3>
                <p class="a-v-panel-item-description">View and manage customer disputes and resolution cases.</p>
                <a href="{{ route('vendor.disputes.index') }}" class="a-v-panel-item-link">View Disputes</a>
            </div>
        </div>
    </div>
</div>
@endsection
