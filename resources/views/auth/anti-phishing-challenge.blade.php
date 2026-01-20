@extends('layouts.auth')
@section('title', 'Security Verification - ' . config('app.name'))
@section('content')

<div class="hecate-login-box">
    <div class="hecate-security-header">
        <h2>Address Verification</h2>
        <span class="security-badge">{{ config('app.name') }}</span>
    </div>

    @if($rateLimited)
        {{-- Rate Limited State --}}
        <div class="anti-phishing-lockout">
            <div class="lockout-icon">⚠️</div>
            <h3 class="lockout-title">Too Many Attempts</h3>
            <p class="lockout-message">
                You have exceeded the maximum number of verification attempts.
                Please wait before trying again.
            </p>
            <div class="lockout-timer">
                <span class="timer-label">Time Remaining:</span>
                <span class="timer-value">{{ $remainingMinutes }} minute{{ $remainingMinutes !== 1 ? 's' : '' }}</span>
            </div>
            <div class="hecate-links">
                <a href="{{ route('anti-phishing.challenge') }}">Try Again</a>
            </div>
        </div>
    @else
        {{-- Challenge State --}}
        <div class="anti-phishing-instructions">
            <p class="instruction-text">
                <strong>Verify you're on the real {{ config('app.name') }}</strong>
            </p>
            <p class="instruction-detail">
                Type the missing characters from our official .onion address below.
                Each <span class="masked-indicator">***</span> represents one hidden character.
            </p>
        </div>

        <div class="anti-phishing-address-display">
            <span class="address-label">Official Address:</span>
            <div class="masked-address">{{ $maskedAddress }}</div>
        </div>

        <div class="anti-phishing-timer">
            <span class="timer-icon">⏱️</span>
            <span class="timer-label">Time Remaining:</span>
            @php
                $minutes = floor($timeRemaining / 60);
                $seconds = $timeRemaining % 60;
            @endphp
            <span class="timer-value">{{ sprintf('%02d:%02d', $minutes, $seconds) }}</span>
        </div>

        <form action="{{ route('anti-phishing.verify') }}" method="POST" class="anti-phishing-form">
            @csrf
            
            <div class="anti-phishing-inputs">
                <label class="hecate-label">Enter the {{ count($positions) }} missing characters:</label>
                <div class="character-inputs">
                    @foreach($positions as $index => $position)
                        <div class="character-input-group">
                            <span class="position-label">Position {{ $position + 1 }}</span>
                            <input 
                                type="text" 
                                name="characters[{{ $index }}]" 
                                class="character-input" 
                                maxlength="1" 
                                required
                                autocomplete="off"
                                autocapitalize="off"
                                spellcheck="false"
                            >
                        </div>
                    @endforeach
                </div>
            </div>

            <button type="submit" class="hecate-submit-btn">Verify Address</button>
        </form>

        <div class="anti-phishing-help">
            <p class="help-text">
                <strong>Why this check?</strong> Phishing sites cannot know our real address.
                By verifying you know the address, you prove you're on the legitimate site.
            </p>
        </div>
    @endif
</div>

@endsection
