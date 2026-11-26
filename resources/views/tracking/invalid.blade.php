@extends('layouts.public')

@section('title', __('Tracking unavailable'))
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
            <div class="mb-5"><x-feedback.error :message="$message" /></div>
            <p class="text-xs font-black uppercase tracking-[0.24em] text-[var(--accent-text)]">MDRRMO · {{ __('Report tracking') }}</p>
            <h1 class="mt-4 text-3xl font-black text-[var(--text)]">{{ __('Tracking unavailable') }}</h1>
            <p class="mt-3 leading-7 text-[var(--text-muted)]">{{ $message }}</p>
            <a href="{{ route('landing') }}" class="mt-6 inline-flex rounded-full bg-[var(--accent)] px-5 py-2.5 text-sm font-black text-[var(--on-accent)] hover:brightness-110">{{ __('Back to home') }}</a>
        </section>
        </main>
@endsection