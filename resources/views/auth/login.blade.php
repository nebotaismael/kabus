@extends('layouts.auth')
@section('content')

<div class="hecate-login-box">
    <div class="hecate-security-header">
        <h2>Security Check</h2>
        <span class="security-badge">{{ config('app.name') }}</span>
    </div>
    <form action="{{ route('login') }}" method="POST">
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
        </div>
        <div class="hecate-form-group">
            <div class="hecate-captcha-terminal">
                <span class="captcha-label">CAPTCHA</span>
                <img src="{{ new Mobicms\Captcha\Image($captchaCode) }}" alt="CAPTCHA Image" class="captcha-image">
                <input type="text" id="captcha" name="captcha" class="captcha-input" required minlength="2" maxlength="8">
            </div>
        </div>
        <button type="submit" class="hecate-submit-btn">Login</button>
    </form>
    <div class="hecate-links">
        <a href="{{ route('register') }}">Create an Account</a>
        <span class="hecate-links-separator">|</span>
        <a href="{{ route('password.request') }}">Forgot Password</a>
    </div>
</div>
@endsection
