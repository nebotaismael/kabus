@extends('layouts.app')

@section('content')
<div class="canary-index-container">
    <div class="canary-index-card">
        <h2 class="canary-index-title">{{ config('app.name') }} Official PGP Key</h2>

        <form method="POST" action="{{ route('admin.pgp-key.post') }}" class="canary-index-form">
            @csrf

            <div class="canary-index-form-group text-center">
                <label for="pgp_key" class="canary-index-label">Official PGP Public Key</label>
                <textarea id="pgp_key" class="canary-index-textarea" name="pgp_key" required rows="15">{{ old('pgp_key', $currentPgpKey) }}</textarea>
            </div>

            <div class="canary-index-form-group text-center">
                <button type="submit" class="canary-index-btn">
                    Update PGP Key
                </button>
            </div>
        </form>
    </div>
</div>

@endsection
