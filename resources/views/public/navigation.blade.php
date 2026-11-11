<header class="civic-masthead sticky top-0 z-50 border-b border-white/10 bg-opacity-95 backdrop-blur-xl">
    <div class="mx-auto grid max-w-7xl grid-cols-[1fr_auto] items-center gap-x-3 gap-y-3 px-4 py-3 sm:px-6 md:grid-cols-[auto_1fr_auto] lg:px-8">
        <a href="{{ route('landing') }}" class="flex min-w-0 items-center gap-3 py-1 text-white">
            @include('components.brand-marks')
            <span class="min-w-0"><span class="block text-[10px] font-black uppercase tracking-[0.16em] text-amber-300">Kalibo MDRRMO · SOU</span><span class="block truncate text-xs font-semibold text-white/75">Special Operations Unit</span></span>
        </a>
        <nav aria-label="Main navigation" class="landing-nav col-span-2 row-start-2 flex min-w-0 items-center gap-1 overflow-x-auto md:col-span-1 md:row-auto md:justify-center md:overflow-visible">
            <a href="{{ route('landing') }}" @class(['is-current' => request()->routeIs('landing')])>Home</a>
            <a href="{{ route('about') }}" @class(['is-current' => request()->routeIs('about')])>About</a>
            <a href="{{ route('how-it-works') }}" @class(['is-current' => request()->routeIs('how-it-works')])>How it works</a>
            <a href="{{ route('news.index') }}" @class(['is-current' => request()->routeIs('news.*')])>News &amp; advisories</a>
            <a href="{{ route('public.report') }}" class="landing-nav-report">Report</a>
        </nav>
        <div class="col-start-2 row-start-1 flex items-center justify-end gap-1 md:col-start-3">
            @include('public.language-switcher', ['availableLocales' => app(App\Support\I18n\LocaleCatalog::class)->all(), 'id' => 'public-locale-header', 'compact' => true])
            @if ($includeThemeToggle ?? true)
                @include('components.theme-toggle', ['placement' => 'inline', 'iconOnly' => true, 'minimal' => true])
            @endif
            <a href="{{ route('admin.login') }}" aria-label="MDRRMO admin sign in" title="MDRRMO admin sign in" class="inline-flex h-10 w-10 items-center justify-center p-2 text-white transition hover:text-amber-300 focus-visible:outline-white">
                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2m6-10a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm9-1 1.2.7-.2 1.4 1.2.9-1 1.2.3 1.4-1.5.4-.7 1.2-1.3-.7-1.3.7-.7-1.2-1.5-.4.3-1.4-1-1.2 1.2-.9-.2-1.4L17 10l1-1.2Z"/><circle cx="17" cy="15" r="1.4"/></svg>
            </a>
        </div>
    </div>
</header>
