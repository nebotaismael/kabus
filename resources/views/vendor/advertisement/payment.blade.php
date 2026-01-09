@extends('layouts.app')

@section('content')

<div class="advertisement-payment-container">
    <div class="advertisement-payment-content">
        <div style="text-align: center;">
            <h1 class="advertisement-payment-title">Advertisement Payment</h1>
        </div>

        <div class="advertisement-payment-grid">
            <!-- Payment Details -->
            <div class="advertisement-payment-card">
                <h2 class="advertisement-payment-subtitle">Payment Details</h2>
                <div class="advertisement-payment-details">
                    <div class="advertisement-payment-row">
                        <span class="advertisement-payment-label">Product:</span>
                        <span class="advertisement-payment-value">{{ $advertisement->product->name }}</span>
                    </div>
                    
                    <div class="advertisement-payment-row">
                        <span class="advertisement-payment-label">Selected Slot:</span>
                        <span class="advertisement-payment-value">Slot {{ $advertisement->slot_number }}</span>
                    </div>
                    
                    <div class="advertisement-payment-row">
                        <span class="advertisement-payment-label">Duration:</span>
                        <span class="advertisement-payment-value">{{ $advertisement->duration_days }} {{ Str::plural('day', $advertisement->duration_days) }}</span>
                    </div>
                    
                    @if($advertisement->pay_amount)
                    <div class="advertisement-payment-row">
                        <span class="advertisement-payment-label">Amount to Pay:</span>
                        <span class="advertisement-payment-value advertisement-payment-amount">ɱ{{ number_format($advertisement->pay_amount, 12) }} {{ strtoupper($advertisement->pay_currency ?? 'XMR') }}</span>
                    </div>
                    @endif

                    <div class="advertisement-payment-row">
                        <span class="advertisement-payment-label">Payment Status:</span>
                        <div class="advertisement-payment-status-wrapper">
                            @if($advertisement->payment_completed)
                                <span class="advertisement-payment-status advertisement-payment-status-completed">
                                    Payment Completed
                                </span>
                            @else
                                <span class="advertisement-payment-status advertisement-payment-status-awaiting">
                                    Awaiting Payment
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
                @if(!$advertisement->payment_completed)
                <div class="advertisement-payment-expiry">
                    <p>
                        Payment window expires: {{ $advertisement->expires_at->diffForHumans() }}
                    </p>
                </div>
                @endif
            </div>

            <!-- QR Code and Payment Address -->
            @if(!$advertisement->payment_completed)
            <div class="advertisement-payment-card">
                @if($qrCode)
                <h2 class="advertisement-payment-subtitle">Scan QR Code</h2>
                    <div class="advertisement-payment-qr">
                        <img src="{{ $qrCode }}" alt="Payment QR Code" class="advertisement-payment-qr-image">
                    </div>
                @endif

                @if($advertisement->pay_address)
                <h2 class="advertisement-payment-subtitle" style="margin-top: 20px;">Payment Address</h2>
                <div class="advertisement-payment-address">
                    {{ $advertisement->pay_address }}
                </div>
                @endif
            </div>

            <div class="advertisement-payment-refresh">
                <a href="{{ route('vendor.advertisement.payment', $advertisement->payment_identifier) }}" class="advertisement-payment-refresh-btn">
                    Refresh to check payment status
                </a>
            </div>
            @endif
        </div>

        @if($advertisement->payment_completed)
            <div class="advertisement-payment-success">
                <p class="advertisement-payment-message">
                    Your payment has been completed successfully. Your advertisement will be displayed in slot {{ $advertisement->slot_number }} 
                    for {{ $advertisement->duration_days }} {{ Str::plural('day', $advertisement->duration_days) }}.
                </p>
                @if($advertisement->starts_at && $advertisement->ends_at)
                <p class="advertisement-payment-message">
                    Active from {{ $advertisement->starts_at->format('M d, Y H:i') }} to {{ $advertisement->ends_at->format('M d, Y H:i') }}.
                </p>
                @endif
            </div>
        @else
            <div class="advertisement-payment-instructions">
                @if($advertisement->pay_amount)
                <p class="advertisement-payment-message">
                    Please send exactly ɱ{{ number_format($advertisement->pay_amount, 12) }} {{ strtoupper($advertisement->pay_currency ?? 'XMR') }} to the address above. 
                    The payment will be detected automatically once confirmed on the blockchain.
                </p>
                @endif
                <p class="advertisement-payment-warning">
                    Payment is processed automatically via webhook. Please refresh this page to check for payment confirmation.
                </p>
                <p class="advertisement-payment-note" style="text-align: center;">
                    Click the refresh button above to check payment status.
                </p>
            </div>
        @endif

        <div class="advertisement-payment-footer">
            <a href="{{ route('vendor.my-products') }}" class="advertisement-payment-back-btn">
                Return to My Products
            </a>
        </div>
    </div>
</div>
@endsection
