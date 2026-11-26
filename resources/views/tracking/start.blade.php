@extends('layouts.public')

@section('title', __('Track a report'))
@section('robots', 'noindex,nofollow')
@section('body-class', 'public-surface public-ambulance-surface min-h-screen antialiased')
@section('body-style')
    background-image: url('{{ asset('assets/ambulance.jpg') }}');
    background-size: cover;
    background-position: center;
    background-attachment: fixed;
@endsection

@section('content')
    <main class="flex flex-1 items-center justify-center px-4 py-10">
        <section class="civic-panel w-full max-w-lg rounded-[6px] p-8 backdrop-blur">
            <h1 class="text-3xl font-black text-[var(--text)]">{{ __('Track a report') }}</h1>
            <p class="mt-3 leading-7 text-[var(--text-muted)]">{{ __('Enter the report number and private tracking code from your tracking link.') }}</p>

            <form action="{{ route('track') }}" method="get" class="mt-6 space-y-4">
                <div>
                    <label for="tracking-reference" class="mb-2 block text-sm font-bold text-[var(--text)]">{{ __('Report number') }}</label>
                    <input id="tracking-reference" name="reference" type="text" autocomplete="off" required class="w-full rounded-xl border border-[var(--border)] bg-[var(--surface-card)] px-4 py-3 text-[var(--text)]" placeholder="{{ __('For example, MDR-2026-000001') }}">
                </div>
                <div>
                    <label for="tracking-code" class="mb-2 block text-sm font-bold text-[var(--text)]">{{ __('Private tracking code') }}</label>
                    <input id="tracking-code" name="token" type="text" autocomplete="off" autocapitalize="off" spellcheck="false" required class="w-full rounded-xl border border-[var(--border)] bg-[var(--surface-card)] px-4 py-3 font-mono text-sm text-[var(--text)]">
                </div>
                <p class="text-sm leading-6 text-[var(--text-muted)]">{{ __('The report number alone cannot open a report. Keep your tracking link and code private.') }}</p>
                <button type="submit" class="inline-flex min-h-11 items-center justify-center rounded-full bg-[var(--accent)] px-5 py-2.5 text-sm font-black text-[var(--on-accent)] hover:brightness-110">{{ __('View report') }}</button>
            </form>
        </section>
    </main>
@endsection
