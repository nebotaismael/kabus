@extends('layouts.app')

@section('content')
<div class="become-vendor-payment-container">
    @if(!$hasPgpVerified)
        <div class="become-vendor-index-highlight" role="alert">
            <img src="{{ asset('images/information.png') }}" alt="Information" class="become-vendor-index-info-icon">
            <div class="become-vendor-index-highlight-content">
                <h4 class="become-vendor-index-highlight-heading">PGP Verification Required!</h4>
                <p class="become-vendor-index-highlight-text">
                    For security reasons, you must verify your PGP key before becoming a vendor. This is a mandatory requirement to ensure secure communication with your customers.
                </p>
                <hr class="become-vendor-index-divider">
                <p class="become-vendor-index-highlight-text become-vendor-index-mb-0">
                    Please visit your Account page to set up and verify your PGP key first.
                </p>
            </div>
        </div>
    @endif

    @if(!$hasMoneroAddress)
        <div class="become-vendor-index-highlight" role="alert">
            <img src="{{ asset('images/information.png') }}" alt="Information" class="become-vendor-index-info-icon">
            <div class="become-vendor-index-highlight-content">
                <h4 class="become-vendor-index-highlight-heading">Return Address Required!</h4>
                <p class="become-vendor-index-highlight-text">
                    You must add at least one return address before becoming a vendor. This is required to ensure secure and reliable payment processing.
                </p>
                <hr class="become-vendor-index-divider">
                <p class="become-vendor-index-highlight-text become-vendor-index-mb-0">
                    Please visit your Addresses page to add a return address first.
                </p>
            </div>
        </div>
    @endif

    @if($hasPgpVerified && $hasMoneroAddress)
        <div class="become-vendor-payment-card">
            @if(isset($error))
                <div class="become-vendor-payment-alert become-vendor-payment-alert-danger text-center">
                    <h2>Error</h2>
                    <p>{{ $error }}</p>
                </div>
            @elseif(isset($alreadyVendor) && $alreadyVendor)
                <div class="become-vendor-payment-alert become-vendor-payment-alert-info text-center">
                    <h2>You Are Already a Vendor</h2>
                    <p>Your account already has vendor privileges. No additional payment is needed.</p>
                </div>
            @elseif(isset($vendorPayment))
                <div class="become-vendor-payment-info">
                    <div class="become-vendor-payment-details text-center">
                        <p>
                            <strong>Vendor Fee:</strong>
                            <span class="become-vendor-payment-amount">${{ number_format(config('marketplace.vendor_fee_usd', 250), 2) }} USD</span>
                        </p>
                        <p>
                            <strong>Payment Address:</strong>
                            <span class="become-vendor-payment-address">{{ $vendorPayment->address }}</span>
                        </p>
                        <p>
                            <strong>Pay in:</strong>
                            <span class="become-vendor-payment-amount">{{ strtoupper($vendorPayment->pay_currency ?? 'XMR') }}</span>
                        </p>
                        <p>
                            <strong>Status:</strong>
                            @if($vendorPayment->payment_completed)
                                <span class="become-vendor-payment-status become-vendor-payment-status-success">
                                    Payment Successful!
                                </span>
                                <div class="become-vendor-payment-next-steps">
                                    <p>Your payment has been received. You can now proceed with your vendor application.</p>
                                    <div class="become-vendor-payment-actions">
                                        <a href="{{ route('become.vendor') }}" class="become-vendor-payment-btn">
                                            Return to Application Page
                                        </a>
                                    </div>
                                </div>
                            @else
                                <span class="become-vendor-payment-status become-vendor-payment-status-info">
                                    Awaiting Payment
                                </span>
                            @endif
                        </p>
                        @if(!$vendorPayment->payment_completed)
                            <div class="become-vendor-payment-notice">
                                <p><strong>Important:</strong> Send the equivalent amount in {{ strtoupper($vendorPayment->pay_currency ?? 'XMR') }} to the payment address. The payment status will update automatically once your transaction is confirmed.</p>
                            </div>
                        @endif
                    </div>
                </div>
                @if($qrCodeDataUri && !$vendorPayment->payment_completed)
                    <div class="become-vendor-payment-qr">
                        <img src="{{ $qrCodeDataUri }}" alt="Payment Address QR Code" class="become-vendor-payment-qr-image">
                    </div>
                @endif
                @if(!$vendorPayment->payment_completed)
                    <div class="become-vendor-payment-refresh">
                        <a href="{{ route('become.payment') }}" class="become-vendor-payment-btn">
                            Refresh to check payment status
                        </a>
                    </div>
                @endif
            @else
                <p class="become-vendor-payment-error">
                    Error occurred while creating payment address. Please try again later.
                </p>
            @endif
        </div>
    @endif
</div>
@endsection
