@extends('layouts.app')

@section('content')
<div class="logs-show-container">
    <div class="logs-show-header">
        <a href="{{ route('admin.index') }}" class="logs-show-back-link">Return to Admin Panel</a>
        <h2 class="logs-show-title">Search Terms Tracking</h2>
    </div>

    {{-- Popular Terms Section --}}
    @if($popularTerms->isNotEmpty())
    <div class="a-v-panel-card" style="margin-bottom: 20px;">
        <h3 class="a-v-panel-item-title">Popular Search Terms</h3>
        <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-top: 10px;">
            @foreach($popularTerms as $term)
                <span style="background: #2a2a2a; padding: 4px 10px; border-radius: 4px; font-size: 13px;">
                    {{ $term->term }} <span style="color: #888;">({{ $term->count }})</span>
                </span>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Search and Filter --}}
    <div class="logs-show-search-form">
        <form action="{{ route('admin.search-terms') }}" method="GET">
            <div class="logs-show-search-container">
                <input type="text" name="search" placeholder="Filter by term..." value="{{ request('search') }}" class="logs-show-search-input">
                <select name="source" class="logs-show-search-input" style="width: auto;">
                    <option value="">All Sources</option>
                    <option value="products" {{ request('source') == 'products' ? 'selected' : '' }}>Products</option>
                    <option value="home" {{ request('source') == 'home' ? 'selected' : '' }}>Home</option>
                </select>
                <button type="submit" class="logs-show-search-button">Filter</button>
                @if(request('search') || request('source'))
                    <a href="{{ route('admin.search-terms') }}" class="logs-show-clear-link">Clear</a>
                @endif
            </div>
        </form>
    </div>

    {{-- Clear All Button --}}
    <form action="{{ route('admin.search-terms.clear') }}" method="POST" style="margin-bottom: 15px;">
        @csrf
        @method('DELETE')
        <button type="submit" class="logs-show-delete-button" onclick="return confirm('Are you sure you want to clear all search terms?')">
            Clear All Search Terms
        </button>
    </form>

    {{-- Search Terms List --}}
    <div class="logs-show-list">
        @forelse($searchTerms as $term)
            <div class="logs-show-item">
                <div class="logs-show-item-header">
                    <div class="logs-show-meta">
                        <span class="logs-show-datetime">{{ $term->created_at->format('Y-m-d H:i:s') }}</span>
                        <span class="logs-show-type logs-show-type-info">{{ $term->source }}</span>
                    </div>
                </div>
                <div class="logs-show-message-container">
                    <pre class="logs-show-message">Term: {{ $term->term }}
User: {{ $term->user ? $term->user->username : 'Guest' }}
IP: {{ $term->ip_address ?? 'N/A' }}</pre>
                </div>
            </div>
        @empty
            <div class="logs-show-empty">
                <p>No search terms recorded yet.</p>
            </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    @if($searchTerms->hasPages())
        <div style="margin-top: 20px;">
            {{ $searchTerms->links() }}
        </div>
    @endif
</div>
@endsection
