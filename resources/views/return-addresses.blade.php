@extends('layouts.app')

@section('content')

<div class="return-addresses-container">
    <div class="return-addresses-disclaimer">
        Add your cryptocurrency wallet addresses to receive payments and refunds. When you complete sales as a vendor, payments will be sent to your payout address for the corresponding currency. For buyers, refunds will be sent to these addresses. For security, consider using unique addresses and avoid reusing addresses publicly.
    </div>
    
    <div class="return-addresses-card">
        <h3 class="return-addresses-form-title">Add Payout Address</h3>
        <form action="{{ route('return-addresses.store') }}" method="POST">
            @csrf
            <div class="return-addresses-form-group">
                <label for="currency" class="return-addresses-label">Cryptocurrency</label>
                <select name="currency" id="currency" class="return-addresses-select" required>
                    <option value="">Select cryptocurrency...</option>
                    @foreach($supportedCurrencies as $code => $config)
                        <option value="{{ $code }}" {{ old('currency') === $code ? 'selected' : '' }}>
                            {{ $config['name'] }} ({{ $config['symbol'] }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="return-addresses-form-group">
                <label for="address" class="return-addresses-label">Wallet Address</label>
                <input type="text" 
                       class="return-addresses-input" 
                       id="address" 
                       name="address" 
                       placeholder="Enter your wallet address"
                       value="{{ old('address') }}"
                       required
                       minlength="20"
                       maxlength="200">
            </div>
            <div class="return-addresses-submit-container">
                <button type="submit" class="return-addresses-submit-btn">Add Payout Address</button>
            </div>
        </form>
    </div>

    @if($returnAddresses->count() > 0)
        <div class="return-addresses-card">
            <h3 class="return-addresses-form-title">Your Payout Addresses</h3>
            <div class="return-addresses-table-container">
                <table class="return-addresses-table">
                    <thead>
                        <tr>
                            <th>Currency</th>
                            <th>Address</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($returnAddresses as $returnAddress)
                            <tr>
                                <td>
                                    <span class="return-addresses-currency-badge">
                                        {{ $returnAddress->currency_symbol }}
                                    </span>
                                </td>
                                <td class="return-addresses-address-cell">{{ $returnAddress->address }}</td>
                                <td>
                                    <form action="{{ route('return-addresses.destroy', $returnAddress) }}" method="POST" style="display: inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="return-addresses-delete-btn">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <div class="return-addresses-empty">
            You haven't added any payout addresses yet. Add at least one address to receive payments.
        </div>
    @endif
    
    <div class="return-addresses-info">
        <h4>Important Notes:</h4>
        <ul>
            <li>You can add up to 4 addresses per cryptocurrency (16 total).</li>
            <li>Vendor payouts are sent in the same currency the buyer used for payment.</li>
            <li>Make sure you have a payout address for each currency you want to accept.</li>
            <li>Double-check your addresses before adding them - incorrect addresses may result in lost funds.</li>
        </ul>
    </div>
</div>
@endsection
