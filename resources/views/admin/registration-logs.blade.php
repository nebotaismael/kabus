@extends('layouts.app')

@section('content')
<div class="user-list-container">
    <div class="user-list-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <h2 class="user-list-title" style="margin: 0;">Registration Logs</h2>
            @if($logs->count() > 0)
            <form action="{{ route('admin.registration-logs.clear') }}" method="POST" onsubmit="return confirm('Clear all logs?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="user-list-btn" style="background: #dc3545;">Clear All</button>
            </form>
            @endif
        </div>
        
        @if($logs->count() > 0)
        <div class="user-list-table-container">
            <table class="user-list-table">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Registered At</th>
                        <th>IP Hash</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($logs as $log)
                        <tr>
                            <td>{{ $log->username }}</td>
                            <td>{{ $log->registered_at->format('Y-m-d H:i:s') }}</td>
                            <td style="font-size: 0.75rem; font-family: monospace;">{{ Str::limit($log->ip_hash, 16) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="user-list-pagination">
            {{ $logs->links() }}
        </div>
        @else
        <p>No registration logs yet.</p>
        @endif
    </div>
</div>
@endsection
