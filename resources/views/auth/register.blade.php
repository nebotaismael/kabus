@extends('layouts.auth')
@section('content')

<div class="hecate-login-box">
    <div class="hecate-security-header">
        <h2>Security Check</h2>
        <span class="security-badge">{{ config('app.name') }}</span>
    </div>
    <form action="{{ route('register') }}" method="POST">
        @csrf
        <div class="hecate-form-group">
            <label for="username" class="hecate-label">Username</label>
            <input type="text" id="username" name="username" value="{{ old('username') }}" 
                   class="hecate-input-minimal" required minlength="4" maxlength="16" placeholder="Enter username">
        </div>
        <div class="hecate-form-group">
            <label for="password" class="hecate-label">Password</label>
            <input type="password" id="password" name="password" 
                   class="hecate-input-minimal" required minlength="8" maxlength="40" placeholder="Enter password">
            <p class="password-hint">Min 8 chars: uppercase, lowercase, number, and special character</p>
        </div>
        <div class="hecate-form-group">
            <label for="password_confirmation" class="hecate-label">Confirm Password</label>
            <input type="password" id="password_confirmation" name="password_confirmation" 
                   class="hecate-input-minimal" required minlength="8" maxlength="40" placeholder="Confirm password">
        </div>
        <div class="hecate-form-group">
            <label for="reference_code" class="hecate-label">
                Reference Code
                @if(!config('marketplace.require_reference_code', true))
                    <span style="color:#666;font-size:12px;font-weight:400;font-style:italic">(Optional)</span>
                @endif
            </label>
            <input type="text" 
                   id="reference_code" 
                   name="reference_code" 
                   value="{{ old('reference_code') }}"
                   class="hecate-input-minimal"
                   @if(config('marketplace.require_reference_code', true)) required @endif
                   minlength="12" maxlength="20" placeholder="Enter reference code">
        </div>
        <div class="hecate-form-group">
            <div class="hecate-captcha-terminal">
                <span class="captcha-label">CAPTCHA</span>
                <img src="{{ $captchaImage }}" alt="CAPTCHA Image" class="captcha-image">
                <input type="text" id="captcha" name="captcha" class="captcha-input" required minlength="2" maxlength="8">
            </div>
        </div>
        <button type="submit" class="hecate-submit-btn">Register</button>
    </form>
    <div class="hecate-links">
        <a href="{{ route('login') }}">Back to Login</a>
    </div>
</div>
@endsection
