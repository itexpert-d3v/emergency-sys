<aside class="operations-sidebar" aria-label="Operations sidebar">
    <a class="operations-brand" href="{{ route('operations.dashboard') }}" aria-label="SOU operations home">
        @include('components.brand-marks')
        <span><strong>Kalibo MDRRMO</strong><small>Special Operations Unit</small></span>
    </a>
    @include('admin.navigation-links')
    <div class="operations-sidebar-bottom">
        <span class="operations-user"><span class="operations-user-dot" aria-hidden="true"></span>{{ auth()->user()->name }}</span>
        <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="operations-signout">Sign out</button></form>
    </div>
</aside>
<div class="operations-mobile-header">
    <a class="operations-mobile-brand" href="{{ route('landing') }}">@include('components.brand-marks')<span>SOU Operations</span></a>
    <div class="operations-mobile-actions">
        @include('components.theme-toggle', ['placement' => 'inline', 'iconOnly' => true])
        <details class="operations-mobile-menu">
            <summary aria-label="Open operations navigation" title="Open operations navigation">
                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
                <span>Menu</span>
            </summary>
            <div class="operations-mobile-menu-panel">
                @include('admin.navigation-links')
                <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="operations-signout">Sign out</button></form>
            </div>
        </details>
    </div>
</div>
