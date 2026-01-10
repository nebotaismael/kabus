@extends('layouts.app')
@section('title', 'Disclaimer - ' . config('app.name'))
@section('content')

<div class="rules-container">
    <div class="text-center">
        <span class="rules-title">Disclaimer</span>
    </div>
    
    <div class="rules-section">
        <h2>Legal Disclaimer</h2>
        <p>By accessing and using {{ config('app.name') }}, you acknowledge and agree to the following terms:</p>
        
        <div class="rules-item">
            <h3>1. No Liability</h3>
            <p>{{ config('app.name') }} and its operators assume no responsibility or liability for any actions taken by users of this platform. All transactions are conducted at the sole risk of the parties involved.</p>
        </div>
        
        <div class="rules-item">
            <h3>2. User Responsibility</h3>
            <p>Users are solely responsible for ensuring their activities comply with all applicable laws and regulations in their jurisdiction. {{ config('app.name') }} does not endorse, encourage, or facilitate any illegal activities.</p>
        </div>
        
        <div class="rules-item">
            <h3>3. No Warranties</h3>
            <p>This platform is provided "as is" without any warranties, express or implied. We do not guarantee the accuracy, completeness, or reliability of any information or content on this platform.</p>
        </div>
        
        <div class="rules-item">
            <h3>4. Third-Party Content</h3>
            <p>{{ config('app.name') }} is not responsible for any content posted by users or third parties. All listings, descriptions, and communications are the sole responsibility of their respective authors.</p>
        </div>
        
        <div class="rules-item">
            <h3>5. Security</h3>
            <p>While we implement security measures to protect user data, we cannot guarantee absolute security. Users are responsible for maintaining the confidentiality of their account credentials and PGP keys.</p>
        </div>
    </div>
    
    <div class="rules-note">
        <strong>Important:</strong> By using {{ config('app.name') }}, you confirm that you have read, understood, and agree to this disclaimer in its entirety.
    </div>
</div>
@endsection
