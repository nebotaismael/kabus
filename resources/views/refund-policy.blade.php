@extends('layouts.app')
@section('title', 'Refund Policy - ' . config('app.name'))
@section('content')

<div class="rules-container">
    <div class="text-center">
        <span class="rules-title">Refund Policy</span>
    </div>
    
    <div class="rules-section">
        <h2>Refund Terms</h2>
        <p>This policy outlines the terms and conditions for refunds on {{ config('app.name') }}.</p>
        
        <div class="rules-item">
            <h3>1. Dispute Resolution</h3>
            <p>All refund requests must be submitted through the dispute system. Disputes can be opened within the allowed timeframe after an order is marked as sent by the vendor.</p>
        </div>
        
        <div class="rules-item">
            <h3>2. Eligible Refund Reasons</h3>
            <p>Refunds may be granted in the following circumstances:</p>
            <ol>
                <li>Product not received within the expected delivery timeframe</li>
                <li>Product significantly differs from the listing description</li>
                <li>Product is damaged or defective upon arrival</li>
                <li>Vendor fails to provide required delivery information</li>
            </ol>
        </div>
        
        <div class="rules-item">
            <h3>3. Non-Refundable Situations</h3>
            <p>Refunds will not be granted in the following cases:</p>
            <ol>
                <li>Buyer's remorse or change of mind</li>
                <li>Incorrect shipping address provided by buyer</li>
                <li>Order completed and finalized by buyer</li>
                <li>Disputes opened after the allowed timeframe</li>
            </ol>
        </div>
        
        <div class="rules-item">
            <h3>4. Dispute Process</h3>
            <p>When a dispute is opened, both parties will have the opportunity to present evidence. An administrator will review the case and make a final decision. All decisions are final and binding.</p>
        </div>
        
        <div class="rules-item">
            <h3>5. Refund Method</h3>
            <p>Approved refunds will be credited to the buyer's account balance. Refunds are processed in the original payment currency (XMR).</p>
        </div>
        
        <div class="rules-item">
            <h3>6. Vendor Obligations</h3>
            <p>Vendors are expected to accurately describe their products and provide timely shipping. Repeated disputes may result in vendor account suspension.</p>
        </div>
    </div>
    
    <div class="rules-note">
        <strong>Note:</strong> {{ config('app.name') }} reserves the right to modify this refund policy at any time. Users are encouraged to review this policy regularly.
    </div>
</div>
@endsection
