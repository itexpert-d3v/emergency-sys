<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.theme-init')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Operations · Kalibo MDRRMO')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @yield('head')
<style>
    /* ------------------------------------------------------------------
       Soft-UI (neumorphism) tokens
       ------------------------------------------------------------------ */
    .ops {
        --nm-bg: #e0e5ec;
        --nm-hi: #ffffff;
        --nm-lo: #b8bec7;
        --nm-ink: #1c2738;
        --nm-ink-2: #46546a;
        --nm-ink-3: #5a687d;
        --nm-accent: #c8f000;          /* brand lime */
        --nm-accent-ink: #1c2738;
        --nm-sky: #0b6fa8;
        --nm-amber: #9a5b00;
        --nm-green: #0a7a52;
        --nm-red: #b42323;
        --nm-grey: #5a687d;

        --nm-raised: 8px 8px 18px #b8bec7, -8px -8px 18px #ffffff;
        --nm-raised-sm: 3px 3px 6px #b8bec7, -3px -3px 6px #ffffff;
        --nm-raised-xs: 2px 2px 4px #b8bec7, -2px -2px 4px #ffffff;
        --nm-inset: inset 6px 6px 12px var(--nm-lo), inset -6px -6px 12px var(--nm-hi);
        --nm-inset-sm: inset 2px 2px 4px #b8bec7, inset -2px -2px 4px #ffffff;

        /* legacy aliases so existing markup still works */
        --o-ink: var(--nm-ink);
        --o-ink-2: var(--nm-ink-2);
        --o-ink-3: var(--nm-ink-3);
        --o-surface: var(--nm-bg);
        --o-sunken: var(--nm-bg);
        --o-hover: transparent;
        --o-line: transparent;
        --o-line-strong: transparent;
        --o-accent: var(--nm-accent);
        --o-accent-hover: #d4ff2a;
        --o-accent-ink: var(--nm-accent-ink);
        --o-bar: var(--nm-lo);
        --o-bar-hi: var(--nm-ink);
    }

    .dark .ops {
        --nm-bg: #1a1f2c;
        --nm-hi: #252c3b;
        --nm-lo: #11151f;
        --nm-ink: #e9edf4;
        --nm-ink-2: #b5bfce;
        --nm-ink-3: #98a3b5;
        --nm-accent: #d4ff2a;
        --nm-accent-ink: #0f1830;
        --nm-sky: #6cc0f0;
        --nm-amber: #f2b24c;
        --nm-green: #4fd1a0;
        --nm-red: #ff8a84;
        --nm-grey: #98a3b5;
        --nm-raised: 8px 8px 18px #11151f, -8px -8px 18px #252c3b;
        --nm-raised-sm: 3px 3px 6px #11151f, -3px -3px 6px #252c3b;
        --nm-raised-xs: 2px 2px 4px #11151f, -2px -2px 4px #252c3b;
        --nm-inset: inset 6px 6px 12px #11151f, inset -6px -6px 12px #252c3b;
        --nm-inset-sm: inset 2px 2px 4px #11151f, inset -2px -2px 4px #252c3b;
    }

    /* --- Sidebar Theme Variables (neumorphic) --- */
    .operations-shell {
        --side-bg: #e0e5ec;
        --side-border: transparent;
        --side-ink: #1c2738;
        --side-ink-muted: #5a687d;
        --side-hover: transparent;
        --side-active-bg: #e4e9f0;
        --side-active-ink: #1c2738;
        --side-active-accent: #c8f000;
        --side-raised: 3px 3px 6px #b8bec7, -3px -3px 6px #ffffff;
        --side-inset: inset 2px 2px 4px #b8bec7, inset -2px -2px 4px #ffffff;
    }
    .dark .operations-shell {
        --side-bg: #1a1f2c;
        --side-border: transparent;
        --side-ink: #e9edf4;
        --side-ink-muted: #98a3b5;
        --side-hover: transparent;
        --side-active-bg: #1a1f2c;
        --side-active-ink: #e9edf4;
        --side-active-accent: #d4ff2a;
        --side-raised: 3px 3px 6px #11151f, -3px -3px 6px #252c3b;
        --side-inset: inset 2px 2px 4px #11151f, inset -2px -2px 4px #252c3b;
    }

    /* --- Sidebar Polish --- */
    .operations-sidebar {
        position: sticky !important;
        top: 0;
        align-self: start;
        height: 100vh;
        height: 100dvh;
        max-height: 100dvh;
        overflow-y: auto;
        overscroll-behavior: contain;
        background: var(--side-bg) !important;
        border-right: 0 !important;
        box-shadow: 6px 0 18px rgba(0,0,0,.04);
        transition: width 700ms ease-in-out, min-width 700ms ease-in-out, transform .24s cubic-bezier(.2,.8,.2,1) !important;
    }
    .operations-shell { transition: grid-template-columns 700ms ease-in-out; }
    .operations-sidebar :is(.sidebar-link-label, .sidebar-label, .sidebar-brand-label, .appearance-label, .operations-user .ops-trunc) {
        transition: opacity 700ms ease-in-out, max-width 700ms ease-in-out, width 700ms ease-in-out;
    }
    .operations-sidebar .sidebar-link-label,
    .operations-sidebar .sidebar-label { max-width: 13rem; }
    .operations-sidebar .operations-nav {
        grid-auto-flow: row;
        grid-template-columns: minmax(0, 1fr);
        align-content: start;
        gap: .75rem;
    }
    .operations-sidebar .operations-nav a svg { transition: transform 200ms ease-out, color 200ms ease-out; }
    .operations-sidebar .operations-nav a:hover svg { transform: scale(1.08); }
    .operations-mobile-header {
        background: var(--side-bg) !important;
        border-bottom: 0 !important;
        box-shadow: 0 4px 12px rgba(0,0,0,.04);
    }
    .operations-sidebar .operations-brand {
        border-bottom: 0;
        margin-bottom: .5rem;
        position: relative;
        min-height: 56px;
        transition: all 700ms cubic-bezier(.25,1,.5,1);
    }
    .operations-sidebar .operations-brand strong { color: #000000; text-shadow: none; }
    .dark .operations-sidebar .operations-brand strong { color: #d4ff2a; text-shadow: 0 0 8px rgba(212,255,42,.22); }
    .operations-brand-expanded,
    .operations-brand-collapsed {
        transition: opacity 700ms ease-in-out, max-width 700ms ease-in-out, transform 700ms ease-in-out;
    }
    .operations-brand-expanded { display: flex; min-width: 0; align-items: center; gap: 10px; opacity: 1; max-width: 220px; }
    .operations-brand-collapsed { position: absolute; width: 48px; height: 48px; object-fit: contain; opacity: 0; transform: scale(.82); pointer-events: none; }
    .operations-sidebar .operations-brand strong { color: #000000; text-shadow: none; }
    .operations-sidebar .operations-nav-label { color: #000000; text-shadow: none; }
    .operations-sidebar .operations-brand small { color: var(--side-ink-muted); }
    .operations-sidebar .operations-nav-label {
        color: #000000;
        font-weight: 600;
        letter-spacing: .12em;
        margin: 1.25rem .75rem .375rem;
        display: block;
        height: 16px;
        line-height: 16px;
        flex: 0 0 16px;
        overflow: hidden;
    }
    .dark .operations-sidebar .operations-nav-label { color: #d4ff2a; text-shadow: 0 0 8px rgba(212,255,42,.22); }
    .operations-sidebar .operations-nav a {
        position: relative;
        gap: 12px;
        height: 48px;
        min-height: 48px;
        flex: 0 0 48px;
        box-sizing: border-box;
        padding-block: 0;
        color: var(--side-ink-muted);
        border: 0;
        border-radius: 14px;
        font-weight: 600;
        min-height: 2.5rem;
        background: var(--side-bg);
        box-shadow: var(--side-raised);
        transition: all 700ms ease-in-out;
    }
    .operations-sidebar .operations-nav a:hover {
        color: var(--side-ink);
        box-shadow: var(--side-inset);
    }
    .operations-sidebar .operations-nav a.is-active {
        background: var(--side-active-bg);
        color: var(--side-active-ink);
        box-shadow: var(--side-inset);
    }
    .operations-sidebar .operations-nav a.is-active svg {
        color: var(--side-active-accent);
    }
    .operations-sidebar .operations-nav { overflow: visible; }
    .operations-sidebar .operations-nav a[data-tooltip]::after {
        position: absolute;
        z-index: 120;
        top: 50%;
        left: calc(100% + 12px);
        padding: .55rem .75rem;
        border-radius: 10px;
        background: var(--side-bg);
        color: var(--side-ink);
        box-shadow: var(--side-raised);
        content: attr(data-tooltip);
        font-size: .75rem;
        font-weight: 700;
        line-height: 1.2;
        white-space: nowrap;
        opacity: 0;
        pointer-events: none;
        transform: translate(-4px, -50%);
        transition: opacity 160ms ease-out, transform 160ms ease-out;
        visibility: hidden;
    }
    body.sidebar-collapsed .operations-sidebar .operations-nav a[data-tooltip]:hover::after,
    body.sidebar-collapsed .operations-sidebar .operations-nav a[data-tooltip]:focus-visible::after {
        opacity: 1;
        transform: translate(0, -50%);
        visibility: visible;
    }
    .operations-sidebar .operations-sidebar-bottom {
        border-top: 0;
        padding-top: 1rem;
    }
    .operations-sidebar .operations-user {
        display: flex;
        min-height: 42px;
        align-items: center;
        gap: .625rem;
        padding: 0 .75rem;
        font-weight: 600;
        color: var(--side-ink);
        text-decoration: none;
        border-radius: 12px;
    }
    .operations-user-avatar { display: block; width: 32px; height: 32px; flex: none; object-fit: cover; }
    .operations-sidebar .ops-avatar {
        display: grid;
        width: 32px;
        height: 32px;
        flex: 0 0 32px;
        place-items: center;
        object-fit: cover;
        background: var(--side-bg);
        color: #84cc16;
        box-shadow: var(--side-inset);
        border-radius: 50%;
    }
    .dark .operations-sidebar .ops-avatar { color: #d4ff2a; }
    .operations-sidebar .operations-signout {
        display: flex;
        width: 100%;
        min-height: 42px;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
        padding: 0 .85rem;
        border: 0;
        border-radius: 14px;
        font-weight: 600;
        color: var(--side-ink-muted);
        background: var(--side-bg);
        box-shadow: var(--side-raised);
        transition: all 200ms ease-out;
    }
    .operations-sidebar .operations-signout svg { display: block; width: 18px !important; height: 18px !important; flex: 0 0 18px; }
    .operations-sidebar .operations-sidebar-appearance {
        display: flex;
        min-height: 42px;
        align-items: center;
        justify-content: space-between;
        gap: .5rem;
        padding: 0 .75rem;
        border-radius: 12px;
        color: var(--side-ink);
    }
    .operations-sidebar .appearance-label { color: var(--side-ink-muted); font-size: .75rem; font-weight: 700; }
    .operations-sidebar [data-theme-toggle] {
        display: inline-flex;
        width: 40px;
        height: 40px;
        flex: 0 0 40px;
        align-items: center;
        justify-content: center;
        border: 0;
        border-radius: 12px;
        background: var(--side-bg);
        color: var(--side-ink);
        box-shadow: var(--side-raised);
    }
    .operations-sidebar [data-theme-toggle]:hover,
    .operations-sidebar [data-theme-toggle]:active { box-shadow: var(--side-inset); }
    .operations-sidebar [data-theme-toggle] svg { width: 20px; height: 20px; }
    .operations-sidebar .operations-signout:hover {
        color: var(--side-ink);
        box-shadow: var(--side-inset);
    }
    .dark .operations-sidebar .operations-signout:hover {
        color: #d4ff2a;
    }
    .operations-sidebar .operations-signout svg { width: 18px; height: 18px; flex: none; }
    .operations-sidebar-appearance { display: flex; min-height: 42px; align-items: center; justify-content: space-between; gap: .5rem; padding-inline: .5rem; }
    .operations-sidebar-appearance .appearance-label { color: var(--side-ink-muted); font-size: .68rem; font-weight: 800; letter-spacing: .1em; text-transform: uppercase; }
    .operations-topbar { display: flex; min-height: 48px; justify-content: flex-end; margin-bottom: 1rem; }
    .operations-account-menu { position: relative; z-index: 30; }
    .operations-account-menu summary { display: flex; min-height: 44px; align-items: center; gap: .65rem; padding: .3rem .75rem .3rem .35rem; border-radius: 14px; background: var(--nm-bg); color: var(--nm-ink); box-shadow: var(--nm-raised-sm); cursor: pointer; list-style: none; transition: all .2s ease-out; }
    .operations-account-menu summary::-webkit-details-marker { display: none; }
    .operations-account-menu summary:hover { transform: translateY(-2px); }
    .operations-account-menu summary:active,
    .operations-account-menu[open] summary { box-shadow: var(--nm-inset-sm); }
    .operations-account-avatar { width: 34px; height: 34px; flex: none; border-radius: 50%; object-fit: cover; box-shadow: var(--nm-inset-sm); }
    .operations-account-initial { display: grid; place-items: center; background: var(--nm-bg); color: var(--nm-ink); font-weight: 800; }
    .operations-account-text { display: grid; line-height: 1.2; text-align: left; }
    .operations-account-text strong { font-size: .75rem; }
    .operations-account-text small { margin-top: .15rem; color: var(--nm-ink-3); font-size: .65rem; }
    .operations-account-menu > div { position: absolute; top: calc(100% + .6rem); right: 0; display: grid; width: 13rem; gap: .4rem; padding: .6rem; border-radius: 14px; background: var(--nm-bg); box-shadow: var(--nm-raised); }
    .operations-account-menu > div a,
    .operations-account-menu > div button { width: 100%; justify-content: flex-start; }
    .ops-appearance-options { display: flex; flex-wrap: wrap; gap: .75rem; }
    .ops-appearance-options .ops-btn { min-width: 7rem; }
    .ops-appearance-options .ops-btn.is-selected { box-shadow: var(--nm-inset-sm) !important; color: var(--nm-accent); }
    .profile-avatar-preview {
        display: grid;
        width: 112px;
        height: 112px;
        flex: none;
        place-items: center;
        overflow: hidden;
        border-radius: 50%;
        background: var(--nm-bg);
        color: var(--nm-ink-2);
        box-shadow: var(--nm-inset);
        font-size: 2rem;
        font-weight: 800;
    }
    .profile-avatar-preview img { width: 100%; height: 100%; object-fit: cover; }
    .operations-sidebar .operations-user:hover,
    .operations-sidebar .operations-nav a:hover { transform: translateY(-2px); }
    .operations-sidebar .operations-nav a:active,
    .operations-sidebar .operations-user:active,
    .operations-sidebar .operations-signout:active { transform: scale(.98); box-shadow: var(--side-inset) !important; }
    #sidebar-toggle {
        display: grid;
        width: 42px;
        height: 42px;
        flex: none;
        place-items: center;
        align-self: flex-end;
        margin: .75rem .25rem .4rem;
        border: 0;
        border-radius: 13px;
        background: var(--side-bg);
        color: var(--side-ink);
        box-shadow: var(--side-raised);
        transition: all 700ms ease-in-out;
    }
    #sidebar-toggle:hover { transform: translateY(-2px); }
    #sidebar-toggle:active { transform: scale(.98); box-shadow: var(--side-inset); }
    #sidebar-toggle svg { width: 20px; height: 20px; transition: transform 700ms ease-in-out; }

    /* --- Generic Page Head --- */
    .ops .operations-page-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        margin-bottom: 1.75rem;
    }
    .ops .operations-page-head h1 {
        font-size: 1.6rem;
        font-weight: 800;
        letter-spacing: -.02em;
        color: var(--nm-ink);
    }
    .ops .operations-page-head p:not(.operations-eyebrow) {
        margin-top: .35rem;
        font-size: .9rem;
        color: var(--nm-ink-3);
    }
    .ops .operations-eyebrow {
        font-weight: 600;
        letter-spacing: .1em;
        color: var(--nm-ink-3);
    }
    .dark .ops .operations-eyebrow { color: var(--nm-accent); }

    /* --- Cards, Lists, etc. --- */
    .ops .ops-card {
        display: block;
        background: var(--nm-bg);
        color: var(--nm-ink);
        border: 0;
        border-radius: 22px;
        box-shadow: var(--nm-raised);
        text-decoration: none;
    }
    .ops .ops-card-head {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: .75rem 1rem;
        padding: 1.15rem 1.35rem;
        border-bottom: 0;
    }
    .ops .ops-card-title {
        margin: 0;
        font-size: 1rem;
        font-weight: 700;
        letter-spacing: -.01em;
        color: var(--nm-ink);
    }
    .ops .ops-card-sub {
        margin: .2rem 0 0;
        font-size: .82rem;
        color: var(--nm-ink-3);
    }
    .ops .ops-card-foot {
        padding: .85rem 1.35rem;
        border-top: 0;
        font-size: .78rem;
        color: var(--nm-ink-3);
    }
    .ops .ops-label {
        margin: 0;
        font-size: .7rem;
        font-weight: 700;
        letter-spacing: .06em;
        text-transform: uppercase;
        color: var(--nm-ink-3);
    }
    .ops .ops-empty {
        padding: 3rem 1.25rem;
        text-align: center;
        font-size: .9rem;
        color: var(--nm-ink-3);
    }

    /* Buttons – raised at rest, pressed on :active */
    .ops .ops-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: .5rem;
        min-height: 2.4rem;
        padding: 0 1.15rem;
        border: 0 !important;
        border-radius: 999px !important;
        background: var(--nm-bg);
        color: var(--nm-ink);
        font-size: .8125rem;
        font-weight: 700;
        line-height: 1;
        text-decoration: none;
        cursor: pointer;
        box-shadow: var(--nm-raised-sm) !important;
        transition: all .2s ease-out;
    }
    .ops .ops-btn svg { width: 1rem; height: 1rem; flex-shrink: 0; }
    .ops .ops-btn:hover { color: var(--nm-accent); transform: translateY(-2px); }
    .ops .ops-btn:active { box-shadow: var(--nm-inset-sm) !important; transform: scale(.98); }
    .ops .ops-btn-sm { min-height: 2rem; padding: 0 .85rem; font-size: .75rem; }
    .ops .ops-btn-primary {
        background: var(--nm-accent);
        color: var(--nm-accent-ink);
        box-shadow: var(--nm-raised-sm) !important;
    }
    .ops .ops-btn-primary:hover {
        color: var(--nm-accent-ink);
        filter: brightness(1.05);
    }
    .ops .ops-btn-primary:active { box-shadow: var(--nm-inset-sm) !important; }
    .ops .ops-btn-block { width: 100%; }
    .ops .ops-btn:focus-visible {
        outline: 3px solid var(--nm-accent);
        outline-offset: 3px;
    }

    /* Grid lists */
    .ops .ops-list > * + * { border-top: 0; margin-top: .5rem; }
    .ops .ops-grid {
        display: grid;
        gap: .5rem 1rem;
        align-items: center;
        padding: .9rem 1.25rem;
        border-radius: 16px;
        transition: box-shadow .15s ease;
    }
    .ops .ops-cols-head {
        display: none;
        padding: .65rem 1.25rem;
        border-bottom: 0;
        background: transparent;
        font-size: .7rem;
        font-weight: 700;
        letter-spacing: .06em;
        text-transform: uppercase;
        color: var(--nm-ink-3);
    }
    @media (min-width: 1024px) {
        .ops .ops-grid { grid-template-columns: var(--cols); }
        .ops .ops-cols-head {
            display: grid;
            grid-template-columns: var(--cols);
            gap: 1rem;
        }
    }
    .ops .ops-row:hover { box-shadow: var(--nm-inset-sm); }
    .ops .ops-title { margin: 0; font-size: .9rem; font-weight: 700; color: var(--nm-ink); }
    .ops .ops-text { margin: 0; font-size: .8125rem; color: var(--nm-ink-2); }
    .ops .ops-muted { margin: 0; font-size: .78rem; color: var(--nm-ink-3); }
    .ops .ops-mono {
        font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
        font-size: .8125rem;
        font-weight: 600;
        color: var(--nm-ink);
    }
    .ops .ops-num { font-variant-numeric: tabular-nums; }
    .ops .ops-trunc { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .ops .ops-end { display: flex; align-items: center; justify-content: flex-end; gap: .5rem; }

    /* Badges – inset pills */
    .ops .ops-badge {
        --b-ink: var(--nm-ink-2);
        display: inline-flex;
        align-items: center;
        gap: .45rem;
        padding: .3rem .8rem;
        border-radius: 999px;
        background: var(--nm-bg);
        color: var(--b-ink);
        font-size: .75rem;
        font-weight: 700;
        line-height: 1.25;
        white-space: nowrap;
        box-shadow: var(--nm-inset-sm);
    }
    .ops .ops-badge::before {
        content: '';
        width: .45rem;
        height: .45rem;
        border-radius: 999px;
        background: currentColor;
    }
    .ops .b-sky { --b-ink: var(--nm-sky); }
    .ops .b-amber { --b-ink: var(--nm-amber); }
    .ops .b-emerald { --b-ink: var(--nm-green); }
    .ops .b-rose { --b-ink: var(--nm-red); }
    .ops .b-zinc { --b-ink: var(--nm-grey); }

    /* Priority Colors */
    .ops .pr-critical { --p: var(--nm-red); }
    .ops .pr-high { --p: #ea580c; }
    .ops .pr-medium { --p: var(--nm-amber); }
    .ops .pr-low { --p: var(--nm-grey); }
    .ops .ops-prio {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        font-size: .75rem;
        font-weight: 600;
        color: var(--nm-ink-2);
    }
    .ops .ops-prio::before {
        content: '';
        width: .5rem;
        height: .5rem;
        border-radius: 999px;
        background: var(--p, var(--nm-grey));
    }

    /* --- TOP NAVBAR --- */
    .top-navbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 1rem 1.35rem;
        background: var(--nm-bg);
        border: 0;
        border-radius: 22px;
        margin-bottom: 1.75rem;
        box-shadow: var(--nm-raised);
    }
    .nav-icon-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 42px;
        height: 42px;
        border-radius: 50%;
        background: var(--nm-bg);
        border: 0;
        color: var(--nm-ink-2);
        cursor: pointer;
        box-shadow: var(--nm-raised-xs);
        transition: box-shadow .15s ease, color .15s ease;
        position: relative;
    }
    .nav-icon-btn:hover { color: var(--nm-ink); }
    .nav-icon-btn:active { box-shadow: var(--nm-inset-sm); }
    .notif-dropdown {
        position: absolute;
        top: 100%;
        right: 0;
        margin-top: 12px;
        width: 320px;
        background: var(--nm-bg);
        border: 0;
        border-radius: 20px;
        box-shadow: var(--nm-raised);
        z-index: 100;
        opacity: 0;
        visibility: hidden;
        transform: translateY(10px);
        transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .notif-dropdown.active {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }
    .notif-header {
        padding: 14px 18px;
        border-bottom: 0;
        font-weight: 700;
        font-size: 0.875rem;
        color: var(--nm-ink);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .notif-list { max-height: 300px; overflow-y: auto; }
    .notif-item {
        padding: 12px 16px;
        border-bottom: 0;
        display: flex;
        gap: 12px;
        text-decoration: none;
        border-radius: 14px;
        margin: 0 6px 4px;
        transition: box-shadow .15s ease;
    }
    .notif-item:hover { box-shadow: var(--nm-inset-sm); }
    .notif-item:last-child { border-bottom: 0; }
    body.sidebar-collapsed .theme-toggle-wrapper { flex-direction: column; gap: 8px; }

    /* =========================================================
       EXPANDED REPORT PANEL
       ========================================================= */
    .ops .ops-detail {
        display: grid;
        gap: 1.35rem;
        padding: 1.35rem;
        background: var(--nm-bg);
        border: 0;
        border-radius: 22px;
        box-shadow: var(--nm-inset);
    }
    @media (min-width: 1280px) {
        .ops .ops-detail {
            grid-template-columns: minmax(0, 1fr) 22rem;
            align-items: start;
        }
        .ops .ops-chat-panel {
            position: sticky;
            top: 1rem;
            height: min(40rem, calc(100vh - 2rem));
        }
    }
    .ops .ops-col { display: flex; flex-direction: column; gap: 1rem; min-width: 0; }
    .ops .ops-panel {
        background: var(--nm-bg);
        border: 0;
        border-radius: 18px;
        padding: 1.1rem 1.2rem;
        box-shadow: var(--nm-raised-sm);
    }
    .ops .ops-panel-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
        margin-bottom: .85rem;
    }
    .ops .ops-panel-title {
        margin: 0;
        font-size: .875rem;
        font-weight: 700;
        color: var(--nm-ink);
    }
    .ops .ops-panel p.ops-muted { margin: .125rem 0 0; }
    .ops .ops-actions {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: .5rem;
    }
    .ops .ops-panel > .ops-actions { margin-top: 1rem; }
    .ops .ops-btn-danger {
        color: var(--nm-red);
    }
    .ops .ops-btn-danger:hover {
        color: var(--nm-red);
        filter: brightness(1.05);
    }

    .ops .ops-quote {
        margin: 0;
        padding: .85rem 1rem;
        border-left: 0;
        border-radius: 14px;
        background: var(--nm-bg);
        box-shadow: var(--nm-inset-sm);
        font-size: .9375rem;
        line-height: 1.65;
        color: var(--nm-ink);
        max-width: 65ch;
    }
    .ops .ops-facts {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(11rem, 1fr));
        gap: .875rem 1.5rem;
        margin: 1rem 0 0;
    }
    .ops .ops-facts dt {
        font-size: .7rem;
        font-weight: 700;
        letter-spacing: .06em;
        text-transform: uppercase;
        color: var(--nm-ink-3);
    }
    .ops .ops-facts dd {
        margin: .25rem 0 0;
        font-size: .875rem;
        line-height: 1.4;
        color: var(--nm-ink);
    }
    .ops .ops-facts a {
        color: var(--nm-ink);
        text-decoration: underline;
        text-underline-offset: 2px;
    }

    /* ---------- Update | Dispatch + Moderation ---------- */
    .ops .ops-forms-grid {
        display: grid;
        grid-template-columns: minmax(0, 1fr);
        gap: 1rem;
    }
    .ops .ops-side-inner {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    @media (min-width: 768px) {
        .ops .ops-forms-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            align-items: stretch;
        }
        .ops .ops-side { position: relative; min-height: 24rem; }
        .ops .ops-side-inner { position: absolute; inset: 0; }
        .ops .ops-side-inner > .ops-panel--dispatch { flex: none; }
        .ops .ops-mod-card { flex: 1 1 0; min-height: 0; }
    }

    /* ---------- Moderation card ---------- */
    .ops .ops-mod-card {
        display: flex;
        flex-direction: column;
        padding: 0;
        overflow: hidden;
        border-radius: 18px;
        box-shadow: var(--nm-raised-sm);
    }
    .ops .ops-mod-card-head {
        flex: none;
        padding: .85rem 1.1rem;
        border-bottom: 0;
    }
    .ops .ops-mod-scroll {
        flex: 1;
        min-height: 0;
        overflow-y: auto;
        overscroll-behavior: contain;
    }
    .ops .ops-mod-section {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: .5rem;
        padding: .85rem 1.1rem;
    }
    .ops .ops-mod-section + .ops-mod-section { border-top: 0; }
    .ops .ops-mod-section .ops-input { width: 100%; }
    .ops .ops-mod-section .ops-actions { width: 100%; }

    .ops .ops-form { display: flex; flex-direction: column; gap: 1rem; }
    .ops .ops-form-submit { margin-top: auto; }
    .ops .ops-field { display: flex; flex-direction: column; gap: .4rem; }
    .ops .ops-field .ops-input { width: 100%; }

    /* Chips – raised, pressed when checked */
    .ops .ops-chips {
        display: grid;
        grid-template-columns: repeat(var(--cols, 2), minmax(0, 1fr));
        gap: .4rem;
    }
    .ops .ops-chip { position: relative; display: block; cursor: pointer; }
    .ops .ops-chip input { position: absolute; inset: 0; opacity: 0; cursor: pointer; }
    .ops .ops-chip > span {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: .375rem;
        min-height: 2.2rem;
        padding: 0 .6rem;
        border: 0;
        border-radius: 12px;
        background: var(--nm-bg);
        font-size: .75rem;
        font-weight: 700;
        color: var(--nm-ink-2);
        box-shadow: var(--nm-raised-xs);
        transition: box-shadow .15s ease, color .15s ease, background .15s ease;
    }
    .ops .ops-chip:hover > span { color: var(--nm-ink); }
    .ops .ops-chip input:checked + span {
        background: var(--nm-accent);
        color: var(--nm-accent-ink);
        box-shadow: var(--nm-inset-sm);
    }
    .ops .ops-chip input:focus-visible + span {
        outline: 3px solid var(--nm-accent);
        outline-offset: 2px;
    }
    .ops .ops-chip[class*="pr-"] > span::before {
        content: '';
        width: .5rem;
        height: .5rem;
        border-radius: 999px;
        background: var(--p, var(--nm-grey));
    }

    .ops .ops-panel--dispatch {
        border-left: 0;
        box-shadow: var(--nm-raised-sm);
    }
    .ops .ops-panel--dispatch { padding: .9rem 1rem; gap: .75rem; }
    .ops .ops-panel--dispatch .ops-unit > span { padding: .65rem .45rem; }

    .ops .ops-units {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: .5rem;
        margin: 0;
        padding: 0;
        border: 0;
        min-width: 0;
    }
    .ops .ops-unit { position: relative; display: block; cursor: pointer; }
    .ops .ops-unit input { position: absolute; inset: 0; opacity: 0; cursor: pointer; }
    .ops .ops-unit > span {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: .4rem;
        padding: .8rem .5rem;
        border: 0;
        border-radius: 14px;
        background: var(--nm-bg);
        font-size: .75rem;
        font-weight: 700;
        text-align: center;
        color: var(--nm-ink-2);
        box-shadow: var(--nm-raised-xs);
        transition: box-shadow .15s ease, color .15s ease;
    }
    .ops .ops-unit svg { width: 1.375rem; height: 1.375rem; }
    .ops .ops-unit:hover > span { color: var(--nm-ink); }
    .ops .ops-unit input:checked + span {
        color: var(--nm-ink);
        box-shadow: var(--nm-inset-sm);
    }
    .ops .ops-unit input:focus-visible + span {
        outline: 3px solid var(--nm-accent);
        outline-offset: 2px;
    }
    .ops .ops-confirm {
        display: flex;
        align-items: flex-start;
        gap: .5rem;
        font-size: .75rem;
        line-height: 1.4;
        color: var(--nm-ink-2);
    }
    .ops .ops-confirm input { margin-top: .125rem; }

    /* Chat panel */
    .ops .ops-chat-panel {
        display: flex;
        flex-direction: column;
        min-height: 24rem;
        overflow: hidden;
        background: var(--nm-bg);
        border: 0;
        border-radius: 20px;
        box-shadow: var(--nm-raised);
    }
    .ops .ops-chat-head {
        flex: none;
        margin: 0;
        padding: 1rem 1.2rem;
        border-bottom: 0;
    }
    .ops .ops-chat-body {
        flex: 1;
        min-height: 0;
        overflow-y: auto;
        padding: 1rem 1.2rem;
        background: var(--nm-bg);
    }
    .ops .ops-thread {
        display: flex;
        flex-direction: column;
        gap: .85rem;
        list-style: none;
        margin: 0;
        padding: 0;
    }
    .ops .ops-thread:empty { display: none; }
    .ops .ops-thread li {
        max-width: 85%;
        padding: .7rem .9rem;
        border-radius: 16px;
        font-size: .875rem;
        line-height: 1.45;
        background: var(--nm-bg);
        color: var(--nm-ink);
        align-self: flex-start;
        box-shadow: var(--nm-inset-sm);
        border-bottom-left-radius: 4px;
    }
    .ops .ops-thread li.is-staff,
    .ops .ops-thread li[data-sender="staff"] {
        align-self: flex-end;
        background: var(--nm-accent);
        color: var(--nm-accent-ink);
        box-shadow: var(--nm-raised-xs);
        border-bottom-left-radius: 16px;
        border-bottom-right-radius: 4px;
    }
    .ops .ops-chat-footer {
        flex: none;
        padding: .85rem 1.2rem 1.1rem;
        border-top: 0;
        background: var(--nm-bg);
    }
    .ops .ops-chat-footer .ops-input {
        width: 100%;
        resize: none;
        border: 0;
        border-radius: 14px;
        background: var(--nm-bg);
        box-shadow: var(--nm-inset-sm);
        padding: .7rem .9rem;
        color: var(--nm-ink);
    }
    .ops .ops-chat-actions {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: .5rem;
        margin-top: .6rem;
    }

    /* =========================================================
       LIGHTBOX MODAL
       ========================================================= */
    .ops-modal-overlay {
        position: fixed;
        inset: 0;
        z-index: 9999;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(0, 0, 0, 0.7);
        backdrop-filter: blur(6px);
        opacity: 0;
        transition: opacity 0.2s ease;
    }
    .ops-modal-overlay.hidden { display: none !important; }
    .ops-modal-overlay.show { opacity: 1; }

    .ops-modal-content {
        position: relative;
        max-width: 90vw;
        max-height: 90vh;
    }

    .ops-modal-img {
        max-width: 100%;
        max-height: 90vh;
        border-radius: 16px;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.45);
        object-fit: contain;
    }

    .ops-modal-close {
        position: absolute;
        top: -0.75rem;
        right: -0.75rem;
        background: var(--nm-bg);
        color: var(--nm-ink);
        border: 0;
        border-radius: 50%;
        width: 2.25rem;
        height: 2.25rem;
        font-size: 1.25rem;
        line-height: 1;
        cursor: pointer;
        box-shadow: var(--nm-raised-sm);
        display: flex;
        align-items: center;
        justify-content: center;
        transition: box-shadow .15s ease;
    }
    .ops-modal-close:hover { box-shadow: var(--nm-inset-sm); }

    /* Inputs (shared) */
    .ops .ops-input {
        border: 0;
        border-radius: 12px;
        background: var(--nm-bg);
        box-shadow: var(--nm-inset-sm);
        color: var(--nm-ink);
        padding: .6rem .85rem;
        font-size: .875rem;
        font-weight: 500;
    }
    .ops .ops-input:focus {
        outline: 3px solid var(--nm-accent);
        outline-offset: 2px;
    }

    .ops-summary-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 1rem;
        margin: 0 0 1.25rem;
    }
    @media (min-width: 1024px) {
        .ops-summary-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); }
    }
    .ops-summary-card {
        display: flex;
        min-width: 0;
        min-height: 108px;
        align-items: center;
        gap: .85rem;
        padding: 1rem;
        border: 0;
        border-radius: 14px;
        background: var(--nm-bg);
        color: var(--nm-ink);
        text-decoration: none;
        box-shadow: var(--nm-raised-sm);
        transition: all .2s ease-out;
    }
    .ops-summary-card:hover { color: var(--nm-ink); transform: translateY(-2px); }
    .ops-summary-card:active,
    .ops-summary-card[aria-current="page"] {
        box-shadow: var(--nm-inset-sm);
        transform: scale(.98);
    }
    .ops-summary-card:focus-visible { outline: 3px solid var(--nm-accent); outline-offset: 3px; }
    .ops-summary-icon {
        display: grid;
        width: 42px;
        height: 42px;
        flex: none;
        place-items: center;
        border-radius: 12px;
        background: var(--nm-bg);
        color: var(--nm-ink-2);
        box-shadow: var(--nm-inset-sm);
        transition: transform .2s ease-out, color .2s ease-out;
    }
    .ops-summary-icon svg { width: 21px; height: 21px; }
    .ops-summary-card:hover .ops-summary-icon { color: var(--nm-accent); transform: scale(1.06); }
    .ops-summary-copy { display: grid; min-width: 0; gap: .3rem; }
    .ops-summary-title { color: var(--nm-ink-2); font-size: .75rem; font-weight: 700; line-height: 1.25; }
    .ops-summary-value { color: var(--nm-ink); font-size: 1.5rem; font-variant-numeric: tabular-nums; font-weight: 800; line-height: 1; }
    .ops-summary-badge { width: fit-content; color: var(--nm-ink-3); font-size: .65rem; font-weight: 700; text-transform: uppercase; }
    .dark .ops-summary-title { color: #d3dae5; }
    .dark .ops-summary-badge { color: #b6c0cf; }

    .operations-nav a { transition: all .2s ease-out !important; }
    .operations-nav a:hover { transform: translateY(-2px); }
    .operations-nav a:active { transform: scale(.98); box-shadow: var(--side-inset) !important; }

    body.operations-surface {
        background: #e0e5ec !important;
        background-image: none !important;
        color: #1c2738;
    }
    .dark body.operations-surface { background: #1a1f2c !important; color: #e9edf4; }
    .operations-shell { min-height: 100dvh; background: var(--side-bg); }
    .operations-main {
        min-width: 0;
        background: var(--nm-bg) !important;
        color: var(--nm-ink);
    }
    .ops .ops-card { border-radius: 14px; }
    .ops .ops-panel { border-radius: 12px; }
    .ops :is(input:not([type='hidden']):not([type='checkbox']):not([type='radio']), select, textarea) {
        border: 0 !important;
        border-radius: 10px;
        background: var(--nm-bg) !important;
        color: var(--nm-ink) !important;
        box-shadow: var(--nm-inset-sm) !important;
    }
    .ops :is(input, select, textarea):focus-visible {
        outline: 3px solid var(--nm-accent) !important;
        outline-offset: 2px;
    }
    .ops :is(button, input[type='submit']):focus-visible,
    .operations-nav a:focus-visible,
    .operations-signout:focus-visible {
        outline: 3px solid var(--nm-accent);
        outline-offset: 3px;
    }
    .operations-mobile-header { display: none; }
    .operations-sidebar-backdrop { display: none; }

    @media (min-width: 640px) and (max-width: 1024px) {
        .operations-shell { grid-template-columns: 72px minmax(0, 1fr) !important; }
        .operations-sidebar { display: flex !important; width: 5rem !important; min-width: 5rem !important; padding-inline: 10px !important; }
        body.sidebar-expanded .operations-shell { grid-template-columns: 16rem minmax(0, 1fr) !important; }
        body.sidebar-expanded .operations-sidebar { width: 16rem !important; min-width: 16rem !important; padding-inline: 14px !important; }
        body.sidebar-collapsed .operations-sidebar :is(.sidebar-link-label, .sidebar-brand-label, .appearance-label, .operations-user .ops-trunc) { width: 0 !important; max-width: 0 !important; max-height: 0 !important; opacity: 0 !important; overflow: hidden !important; white-space: nowrap !important; padding: 0 !important; margin: 0 !important; }
        body.sidebar-collapsed .operations-sidebar .sidebar-label { width: 0 !important; max-width: 0 !important; opacity: 0 !important; overflow: hidden !important; white-space: nowrap !important; }
        body.sidebar-collapsed .operations-sidebar .operations-brand-expanded { max-width: 0; opacity: 0; overflow: hidden; }
        body.sidebar-collapsed .operations-sidebar .operations-brand-collapsed { opacity: 1; transform: scale(1); }
        body.sidebar-collapsed .operations-sidebar .operations-brand,
        body.sidebar-collapsed .operations-sidebar .operations-nav a,
        body.sidebar-collapsed .operations-sidebar-bottom > * { justify-content: center; }
        body.sidebar-collapsed .operations-sidebar .operations-nav a { gap: 0; padding-inline: 0; }
        body.sidebar-collapsed .operations-sidebar .operations-brand { position: relative; min-height: 56px; justify-content: center; padding-inline: 0; }
        body.sidebar-collapsed .operations-sidebar .operations-brand-collapsed { display: block; }
        body.sidebar-collapsed #sidebar-toggle svg { transform: rotate(180deg); }
        body.sidebar-collapsed .operations-sidebar-appearance { width: 42px; padding: 0; }
        body.sidebar-collapsed .operations-sidebar .operations-signout { width: 42px; margin-inline: auto; justify-content: center; padding: 0; }
        .operations-mobile-header { display: none !important; }
    }

    @media (min-width: 1025px) {
        .operations-shell { grid-template-columns: 16rem minmax(0, 1fr) !important; }
        .operations-sidebar { display: flex !important; }
        body.sidebar-collapsed .operations-shell { grid-template-columns: 5rem minmax(0, 1fr) !important; }
        body.sidebar-collapsed .operations-sidebar { width: 5rem !important; min-width: 5rem !important; padding-inline: 10px !important; }
        body.sidebar-collapsed .operations-sidebar :is(.sidebar-link-label, .sidebar-brand-label, .appearance-label, .operations-user .ops-trunc) { width: 0 !important; max-width: 0 !important; max-height: 0 !important; opacity: 0 !important; overflow: hidden !important; white-space: nowrap !important; padding: 0 !important; margin: 0 !important; }
        body.sidebar-collapsed .operations-sidebar .sidebar-label { width: 0 !important; max-width: 0 !important; opacity: 0 !important; overflow: hidden !important; white-space: nowrap !important; }
        body.sidebar-collapsed .operations-sidebar .operations-brand-expanded { max-width: 0; opacity: 0; overflow: hidden; }
        body.sidebar-collapsed .operations-sidebar .operations-brand-collapsed { opacity: 1; transform: scale(1); }
        body.sidebar-collapsed .operations-sidebar .operations-brand,
        body.sidebar-collapsed .operations-sidebar .operations-nav a,
        body.sidebar-collapsed .operations-sidebar-bottom > * { justify-content: center; }
        body.sidebar-collapsed .operations-sidebar .operations-nav a { gap: 0; padding-inline: 0; }
        body.sidebar-collapsed .operations-sidebar .operations-brand { position: relative; min-height: 56px; justify-content: center; padding-inline: 0; }
        body.sidebar-collapsed .operations-sidebar .operations-brand-collapsed { display: block; }
        body.sidebar-collapsed #sidebar-toggle svg { transform: rotate(180deg); }
        body.sidebar-collapsed .operations-sidebar-appearance { width: 42px; padding: 0; }
        body.sidebar-collapsed .operations-sidebar .operations-signout { width: 42px; margin-inline: auto; justify-content: center; padding: 0; }
    }

    @media (max-width: 639px) {
        .operations-shell { display: block !important; min-height: 100dvh; }
        .operations-mobile-header {
            position: sticky;
            z-index: 60;
            top: 0;
            display: flex !important;
            min-height: 60px;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 8px 14px;
            background: var(--side-bg);
            box-shadow: 0 4px 12px rgba(0, 0, 0, .08);
        }
        .operations-mobile-brand { min-width: 0; color: var(--side-ink); }
        .operations-mobile-brand > span:last-child { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .operations-mobile-menu-toggle {
            display: inline-flex;
            width: 42px;
            height: 42px;
            flex: none;
            align-items: center;
            justify-content: center;
            border: 0;
            border-radius: 12px;
            background: var(--side-bg);
            color: var(--side-ink);
            box-shadow: var(--side-raised);
        }
        .operations-mobile-menu-toggle:active { box-shadow: var(--side-inset); }
        .operations-mobile-menu-toggle svg { width: 21px; height: 21px; }
        .operations-sidebar {
            position: fixed !important;
            z-index: 81;
            inset: 0 auto 0 0;
            display: flex !important;
            width: min(86vw, 320px) !important;
            min-width: 0 !important;
            height: 100dvh;
            padding: 20px 14px 16px !important;
            overflow-y: auto;
            transform: translateX(-105%);
            transition: transform .24s cubic-bezier(.2, .8, .2, 1);
            box-shadow: 12px 0 30px rgba(0, 0, 0, .2);
        }
        body.mobile-menu-open .operations-sidebar { transform: translateX(0); }
        .operations-sidebar-backdrop {
            position: fixed;
            z-index: 80;
            inset: 0;
            display: block;
            border: 0;
            background: rgba(15, 23, 42, .48);
            opacity: 0;
            pointer-events: none;
            transition: opacity .2s ease;
        }
        body.mobile-menu-open .operations-sidebar-backdrop { opacity: 1; pointer-events: auto; }
        #sidebar-toggle { display: none !important; }
        .operations-main { padding: 22px 14px 36px !important; }
        .operations-page-head { flex-direction: column; align-items: stretch; gap: 12px; }
        .operations-head-actions { justify-content: flex-start; }
        .ops .ops-card-head { padding: 1rem; }
    }

    @media (prefers-reduced-motion: reduce) {
        .ops .ops-btn,
        .ops .ops-chip > span,
        .ops .ops-unit > span,
        .ops .ops-summary-card,
        .operations-sidebar .operations-nav a,
        .nav-icon-btn,
        .operations-sidebar,
        .operations-shell,
        .operations-sidebar :is(.sidebar-link-label, .sidebar-label, .sidebar-brand-label, .appearance-label, .operations-user .ops-trunc),
        .operations-brand-expanded,
        .operations-brand-collapsed,
        #sidebar-toggle,
        #sidebar-toggle svg {
            transition: none !important;
            will-change: auto;
        }
    }
</style>
@yield('styles')
</head>
<body class="operations-surface min-h-screen antialiased @yield('body-class')">
<div class="operations-shell">
    @include('admin.sidebar')
    <main class="operations-main ops @yield('main-class')">
        <div class="operations-topbar">
            <details class="operations-account-menu">
                <summary aria-label="Open account menu">
                    @if (auth()->user()->avatar_path)
                        <img class="operations-account-avatar" src="{{ asset('storage/' . auth()->user()->avatar_path) }}" alt="">
                    @else
                        <span class="operations-account-avatar operations-account-initial" aria-hidden="true">{{ \Illuminate\Support\Str::upper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                    @endif
                    <span class="operations-account-text"><strong>{{ auth()->user()->name }}</strong><small>{{ ucfirst(str_replace('_', ' ', auth()->user()->role)) }}</small></span>
                    <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" style="width:16px;height:16px"><path stroke-linecap="round" stroke-linejoin="round" d="m7 10 5 5 5-5"/></svg>
                </summary>
                <div>
                    <a href="{{ route('profile.edit') }}" class="ops-btn ops-btn-sm">Profile</a>
                    <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="ops-btn ops-btn-sm">Sign out</button></form>
                </div>
            </details>
        </div>
        @yield('content')
    </main>
</div>
@yield('scripts')
</body>
</html>