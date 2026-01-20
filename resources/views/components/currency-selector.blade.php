@props(['currencies', 'selected', 'action'])

<div class="currency-selector">
    <form method="POST" action="{{ $action }}" class="currency-selector-form">
        @csrf
        <label for="currency" class="currency-selector-label">Pay with:</label>
        <select name="currency" id="currency" class="currency-selector-select" onchange="this.form.submit()">
            @foreach($currencies as $code => $config)
                <option value="{{ $code }}" {{ $selected === $code ? 'selected' : '' }}>
                    {{ $config['name'] }} ({{ $config['symbol'] }})
                </option>
            @endforeach
        </select>
        <noscript>
            <button type="submit" class="currency-selector-btn">Change Currency</button>
        </noscript>
    </form>
</div>
