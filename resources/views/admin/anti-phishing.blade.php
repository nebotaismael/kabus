@extends('layouts.app')

@section('content')

<div class="anti-phishing-admin-container">
    <div class="anti-phishing-admin-card">
        <h1 class="anti-phishing-admin-title">Anti-Phishing Settings</h1>
        <p class="anti-phishing-admin-description">Configure the address verification challenge that protects users from phishing attacks.</p>

        @if (session('success'))
            <div class="anti-phishing-admin-alert anti-phishing-admin-alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="anti-phishing-admin-alert anti-phishing-admin-alert-error">
                {{ session('error') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="anti-phishing-admin-alert anti-phishing-admin-alert-error">
                <ul class="anti-phishing-admin-error-list">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.anti-phishing.update') }}" class="anti-phishing-admin-form">
            @csrf

            <div class="anti-phishing-admin-form-group">
                <label class="anti-phishing-admin-label" for="enabled">Feature Status</label>
                <div class="anti-phishing-admin-toggle-container">
                    <button type="submit" name="enabled" value="1" class="anti-phishing-admin-toggle-button{{ $settings['enabled'] ? ' active' : '' }}">
                        ENABLED
                    </button>
                    <button type="submit" name="enabled" value="0" class="anti-phishing-admin-toggle-button{{ !$settings['enabled'] ? ' active' : '' }}">
                        DISABLED
                    </button>
                </div>
                <p class="anti-phishing-admin-help-text">When enabled, users must complete an address verification challenge before accessing the login form.</p>
            </div>

            <div class="anti-phishing-admin-form-group">
                <label class="anti-phishing-admin-label">Official .onion Addresses</label>
                <p class="anti-phishing-admin-help-text" style="margin-bottom: 10px;">Configure up to 3 official .onion addresses. The system will detect which address the user is accessing and use that for verification.</p>
                
                @for ($i = 0; $i < 3; $i++)
                <div style="margin-bottom: 10px;">
                    <label class="anti-phishing-admin-label" style="font-size: 12px; color: #888;">Address {{ $i + 1 }}{{ $i === 0 ? ' (Primary)' : ' (Optional)' }}</label>
                    <input type="text" 
                           name="onion_addresses[]" 
                           id="onion_address_{{ $i }}" 
                           class="anti-phishing-admin-input" 
                           value="{{ old('onion_addresses.' . $i, $settings['onion_addresses'][$i] ?? '') }}"
                           {{ $i === 0 ? 'required' : '' }}
                           minlength="62"
                           maxlength="62"
                           pattern="[a-zA-Z0-9]{56}\.onion"
                           placeholder="xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx.onion">
                </div>
                @endfor
            </div>

            <div class="anti-phishing-admin-form-row">
                <div class="anti-phishing-admin-form-group anti-phishing-admin-form-group-half">
                    <label class="anti-phishing-admin-label" for="difficulty">Challenge Difficulty</label>
                    <input type="number" 
                           name="difficulty" 
                           id="difficulty" 
                           class="anti-phishing-admin-input" 
                           value="{{ old('difficulty', $settings['difficulty']) }}"
                           required
                           min="2"
                           max="8">
                    <p class="anti-phishing-admin-help-text">Number of characters to mask (2-8). Higher values are more difficult.</p>
                </div>

                <div class="anti-phishing-admin-form-group anti-phishing-admin-form-group-half">
                    <label class="anti-phishing-admin-label" for="time_limit">Time Limit (minutes)</label>
                    <input type="number" 
                           name="time_limit" 
                           id="time_limit" 
                           class="anti-phishing-admin-input" 
                           value="{{ old('time_limit', $settings['time_limit']) }}"
                           required
                           min="1"
                           max="10">
                    <p class="anti-phishing-admin-help-text">Time allowed to complete the challenge (1-10 minutes).</p>
                </div>
            </div>

            <div class="anti-phishing-admin-current-settings">
                <h3 class="anti-phishing-admin-current-title">Current Configuration</h3>
                <div class="anti-phishing-admin-current-grid">
                    <div class="anti-phishing-admin-current-item">
                        <span class="anti-phishing-admin-current-label">Status:</span>
                        <span class="anti-phishing-admin-current-value {{ $settings['enabled'] ? 'status-enabled' : 'status-disabled' }}">
                            {{ $settings['enabled'] ? 'Enabled' : 'Disabled' }}
                        </span>
                    </div>
                    <div class="anti-phishing-admin-current-item">
                        <span class="anti-phishing-admin-current-label">Difficulty:</span>
                        <span class="anti-phishing-admin-current-value">{{ $settings['difficulty'] }} characters</span>
                    </div>
                    <div class="anti-phishing-admin-current-item">
                        <span class="anti-phishing-admin-current-label">Time Limit:</span>
                        <span class="anti-phishing-admin-current-value">{{ $settings['time_limit'] }} minutes</span>
                    </div>
                    <div class="anti-phishing-admin-current-item anti-phishing-admin-current-item-full">
                        <span class="anti-phishing-admin-current-label">Onion Addresses:</span>
                        <div class="anti-phishing-admin-current-value anti-phishing-admin-address">
                            @php
                                $configuredAddresses = array_filter($settings['onion_addresses'], fn($addr) => !empty($addr));
                            @endphp
                            @if (count($configuredAddresses) > 0)
                                @foreach ($configuredAddresses as $index => $address)
                                    <div style="margin-bottom: 5px;">{{ $index + 1 }}. {{ $address }}</div>
                                @endforeach
                            @else
                                Not configured
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="anti-phishing-admin-form-actions">
                <button type="submit" class="anti-phishing-admin-submit">
                    Save Settings
                </button>
                <a href="{{ route('admin.index') }}" class="anti-phishing-admin-cancel">
                    Back to Admin Panel
                </a>
            </div>
        </form>
    </div>
</div>

@endsection
