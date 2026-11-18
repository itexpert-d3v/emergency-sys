@php($role = auth()->user()->role)
<nav class="operations-nav" aria-label="Operations navigation">
    @if (in_array($role, ['admin', 'staff'], true))
        <p class="operations-nav-label sidebar-label w-auto opacity-100 overflow-hidden whitespace-nowrap transition-all duration-200">Operations</p>
        <a href="{{ route('operations.dashboard') }}" @class(['is-active' => request()->routeIs('operations.dashboard')]) title="Dashboard" aria-label="Dashboard" data-tooltip="Dashboard">
            <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><rect x="3" y="3" width="8" height="8" rx="1"/><rect x="13" y="3" width="8" height="5" rx="1"/><rect x="13" y="10" width="8" height="11" rx="1"/><rect x="3" y="13" width="8" height="8" rx="1"/></svg>
            <span class="sidebar-link-label w-auto opacity-100 overflow-hidden whitespace-nowrap transition-all duration-200">Dashboard</span>
        </a>
        <a href="{{ route('operations.live-map') }}" @class(['is-active' => request()->routeIs('operations.live-map')]) title="Live map" aria-label="Live map" data-tooltip="Live Map">
            <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
            <span class="sidebar-link-label w-auto opacity-100 overflow-hidden whitespace-nowrap transition-all duration-200">Live map</span>
        </a>
        <a href="{{ route($role === 'admin' ? 'admin.dashboard.queue' : 'operations.queue') }}" @class(['is-active' => request()->routeIs('admin.dashboard.queue', 'operations.queue')]) title="Incident queue" aria-label="Incident queue" data-tooltip="Incident Queue">
            <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16"/><circle cx="7" cy="6" r="1" fill="currentColor"/><circle cx="7" cy="12" r="1" fill="currentColor"/><circle cx="7" cy="18" r="1" fill="currentColor"/></svg>
            <span class="sidebar-link-label w-auto opacity-100 overflow-hidden whitespace-nowrap transition-all duration-200">Incident queue</span>
        </a>
    @endif

    @if (in_array($role, ['admin', 'staff'], true))
        <p class="operations-nav-label sidebar-label w-auto opacity-100 overflow-hidden whitespace-nowrap transition-all duration-200">Communications</p>
        <a href="{{ route('admin.news.index') }}" @class(['is-active' => request()->routeIs('admin.news.*')]) title="News and advisories" aria-label="News and advisories" data-tooltip="News & advisories">
            <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M5 4h12a2 2 0 0 1 2 2v14H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Zm0 0v14M8 8h7M8 12h7M8 16h4"/></svg>
            <span class="sidebar-link-label w-auto opacity-100 overflow-hidden whitespace-nowrap transition-all duration-200">News &amp; advisories</span>
        </a>
        <a href="{{ route('landing') }}" title="View public site" aria-label="View public site" data-tooltip="View public site">
            <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1h-6v-7h-4v7H4a1 1 0 0 1-1-1V10Z"/></svg>
            <span class="sidebar-link-label w-auto opacity-100 overflow-hidden whitespace-nowrap transition-all duration-200">View public site</span>
        </a>
    @endif

    @if (in_array($role, ['admin', 'staff'], true))
        <p class="operations-nav-label sidebar-label w-auto opacity-100 overflow-hidden whitespace-nowrap transition-all duration-200">Administration</p>
    @endif

    @if ($role === 'admin')
        <a href="{{ route('admin.moderation.index') }}" @class(['is-active' => request()->routeIs('admin.moderation.*')]) title="Safety and deleted reports" aria-label="Safety and deleted reports" data-tooltip="Safety & deleted">
                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3 4.5 6v5.5c0 4.2 2.8 7.7 7.5 9.5 4.7-1.8 7.5-5.3 7.5-9.5V6L12 3Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-3-3v6"/></svg>
            <span class="sidebar-link-label w-auto opacity-100 overflow-hidden whitespace-nowrap transition-all duration-200">Safety &amp; deleted</span>
        </a>
        <a href="{{ route('admin.users.index') }}" @class(['is-active' => request()->routeIs('admin.users.*')]) title="User management" aria-label="User management" data-tooltip="User Management">
            <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><circle cx="9" cy="8" r="3"/><path stroke-linecap="round" stroke-linejoin="round" d="M3 20v-1a6 6 0 0112 0v1M16 5.2a3 3 0 010 5.6M18 14a5 5 0 013 4.6V20"/></svg>
            <span class="sidebar-link-label w-auto opacity-100 overflow-hidden whitespace-nowrap transition-all duration-200">User management</span>
        </a>
        <a href="{{ route('admin.reports.index') }}" @class(['is-active' => request()->routeIs('admin.reports.*')]) title="Reporting" aria-label="Reporting" data-tooltip="Reporting">
            <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M4 19V5m0 14h16M8 15l3-4 3 2 5-7"/></svg>
            <span class="sidebar-link-label w-auto opacity-100 overflow-hidden whitespace-nowrap transition-all duration-200">Reporting</span>
        </a>
    @endif
        @if (in_array($role, ['admin', 'staff'], true))
        <p class="operations-nav-label sidebar-label w-auto opacity-100 overflow-hidden whitespace-nowrap transition-all duration-200">System</p>
            <a href="{{ route('profile.edit') }}" @class(['is-active' => request()->routeIs('profile.*')]) title="Profile" aria-label="Profile" data-tooltip="Profile">
                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><circle cx="12" cy="8" r="3.5"/><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 20a7.5 7.5 0 0115 0"/></svg>
                <span class="sidebar-link-label w-auto opacity-100 overflow-hidden whitespace-nowrap transition-all duration-200">Profile</span>
            </a>
        @endif
    @if ($role === 'admin')
        <a href="{{ route('admin.settings') }}" @class(['is-active' => request()->routeIs('admin.settings')]) title="Settings" aria-label="Settings" data-tooltip="Settings">
            <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8.5a3.5 3.5 0 100 7 3.5 3.5 0 000-7z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.4 15a1.7 1.7 0 00.34 1.88l.06.06-1.6 2.77-.08-.03a1.7 1.7 0 00-1.78.4l-.06.06h-3.2l-.02-.08a1.7 1.7 0 00-1.28-1.29l-.08-.02-1.6-2.77.06-.06A1.7 1.7 0 0010.5 14l-.08-.02v-3.2l.08-.02a1.7 1.7 0 001.28-1.29l.02-.08h3.2l.06.06a1.7 1.7 0 001.78.4l.08-.03 1.6 2.77-.06.06A1.7 1.7 0 0019.4 15z"/></svg>
            <span class="sidebar-link-label w-auto opacity-100 overflow-hidden whitespace-nowrap transition-all duration-200">Settings</span>
        </a>
    @endif

</nav>