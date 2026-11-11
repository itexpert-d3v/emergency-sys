@php($placement = $placement ?? 'fixed')
<button type="button" data-theme-toggle aria-label="Switch to dark mode" title="Switch to dark mode" class="{{ $placement === 'inline' ? 'inline-flex' : 'fixed bottom-5 right-5 z-50 inline-flex' }} minimal-icon-control">
    <svg aria-hidden="true" class="h-5 w-5 dark:hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M20.5 15.5A8.5 8.5 0 0 1 8.5 3.5 8.5 8.5 0 1 0 20.5 15.5Z"/></svg>
    <svg aria-hidden="true" class="hidden h-5 w-5 dark:block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="4"/><path stroke-linecap="round" d="M12 2v2m0 16v2M4.93 4.93l1.42 1.42m11.3 11.3 1.42 1.42M2 12h2m16 0h2M4.93 19.07l1.42-1.42m11.3-11.3 1.42-1.42"/></svg>
</button>
