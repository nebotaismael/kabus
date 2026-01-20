{{--
    Anti-Phishing Badge Component
    
    Displays the user's anti-phishing phrase in a styled badge.
    Only renders if the authenticated user has a phrase set.
    
    Requirements: 2.1, 2.6
    - Display phrase prominently on every authenticated page
    - Show in a consistent, recognizable location (navbar area)
--}}

@auth
    @if(auth()->user()->secretPhrase)
        <div class="anti-phishing-badge" title="Your anti-phishing phrase - if you don't see this, you may be on a phishing site">
            <span class="anti-phishing-badge-icon">🛡️</span>
            <span class="anti-phishing-badge-phrase">{{ auth()->user()->secretPhrase->phrase }}</span>
        </div>
    @endif
@endauth
