<footer class="footer">
    {{-- Top Row: Logo, Text, Listed On --}}
    <div class="footer-top">
        <div class="footer-brand">
            <img src="{{ asset('images/hecate.png') }}" alt="Hecate Market" class="footer-brand-logo">
        </div>
        <div class="footer-brand-text">
            Hecate Market is a secure, anonymous marketplace focused on privacy, Monero payments, and user protection.
        </div>
        <div class="footer-listings">
            <span class="footer-listings-label">Listed on:</span>
            <a href="https://dread.link" target="_blank" rel="noopener" class="footer-listing-link">
                <img src="{{ asset('images/dread.png') }}" alt="Dread" class="footer-listing-icon">
            </a>
            <a href="https://tor.link" target="_blank" rel="noopener" class="footer-listing-link">
                <img src="{{ asset('images/torlink.webp') }}" alt="Tor.link" class="footer-listing-icon">
            </a>
            <a href="https://torrun.com" target="_blank" rel="noopener" class="footer-listing-link">
                <img src="{{ asset('images/torrun.png') }}" alt="TorRun" class="footer-listing-icon">
            </a>
            <a href="https://darkeye.org" target="_blank" rel="noopener" class="footer-listing-link">
                <img src="{{ asset('images/darkeye.jpg') }}" alt="Dark Eye" class="footer-listing-icon">
            </a>
        </div>
    </div>

    {{-- Bottom Row: Buttons, XMR Price, Canary --}}
    <div class="footer-bottom">
        <div class="footer-left">
         
            <a href="{{ route('pgp-key') }}" class="footer-button">PGP Key</a>
        </div>

        <div class="footer-center">
            <div class="footer-xmr-price">
                <span class="footer-xmr-price-label">XMR/USD:</span>
                @php
                    $xmrPrice = app(App\Http\Controllers\XmrPriceController::class)->getXmrPrice();
                @endphp
                <span class="footer-xmr-price-value {{ $xmrPrice === 'UNAVAILABLE' ? 'unavailable' : '' }}">
                    @if($xmrPrice !== 'UNAVAILABLE')
                        ${{ $xmrPrice }}
                    @else
                        {{ $xmrPrice }}
                    @endif
                </span>
            </div>
        </div>

        <div class="footer-right">
            <a href="{{ route('canary') }}" class="footer-button">Canary</a>
            <div class="footer-scroll-top">
           </div>
        </div>
    </div>
</footer>
