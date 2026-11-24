@php
    $sidebarUser = auth()->user();
    $initials = collect(preg_split('/\s+/', trim((string) $sidebarUser->name)))
        ->filter()
        ->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');
@endphp

<aside class="operations-sidebar" id="operations-sidebar" aria-label="Operations sidebar">
    <a class="operations-brand" href="{{ route('operations.dashboard') }}" aria-label="SOU operations home">
        @include('components.brand-marks')
        <span class="operations-brand-copy"><strong>Kalibo MDRRMO</strong><small>Special Operations Unit</small></span>
    </a>
    @include('admin.navigation-links')
    <div class="operations-sidebar-bottom">
        <div class="operations-account">
            <span class="operations-account-avatar operations-account-initial" aria-hidden="true">{{ $initials ?: 'A' }}</span>
            <span class="operations-account-copy"><strong>{{ $sidebarUser->name }}</strong><small>{{ ucfirst($sidebarUser->role) }}</small></span>
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="operations-signout">
                <span>Sign out</span>
                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M10 17l5-5-5-5m5 5H3m12-8h4a2 2 0 012 2v14a2 2 0 01-2 2h-4"/></svg>
            </button>
        </form>
    </div>
</aside>

<div class="operations-mobile-header">
    <a class="operations-mobile-brand" href="{{ route('landing') }}">@include('components.brand-marks')<span>SOU Operations</span></a>
    <div class="operations-mobile-actions">
        @include('components.theme-toggle', ['placement' => 'inline', 'iconOnly' => true])
        <button type="button" class="operations-mobile-menu-toggle" data-mobile-menu-toggle aria-label="Open operations navigation" aria-controls="operations-sidebar" aria-expanded="false">
            <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
    </div>
</div>
<button type="button" class="operations-sidebar-backdrop" data-sidebar-backdrop aria-label="Close operations navigation" tabindex="-1"></button>
