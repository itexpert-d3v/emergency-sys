@extends('layouts.public')

@section('title', __('Emergency Response Portal'))
@section('body-class', 'public-surface public-landing-surface min-h-screen antialiased')
@section('body-style')
@endsection

@push('head')
    @vite(['resources/js/landing-motion.js'])
@endpush

@section('content')
    <div class="landing-theme-overlay min-h-screen">

        <main class="mx-auto max-w-7xl px-4 py-5 sm:px-6 lg:px-8">
            <section id="home" class="landing-hero scroll-mt-28" aria-labelledby="hero-heading">
                <div class="landing-hero-copy mx-auto max-w-4xl" data-hero-copy>
                    <h1 id="hero-heading" class="text-4xl font-black leading-tight text-white sm:text-5xl lg:text-7xl">{{ __('Report incidents quickly and stay informed.') }}</h1>
                    <p class="mx-auto mt-5 max-w-2xl text-base leading-7 text-white sm:text-lg sm:leading-8">{{ __('Residents can submit a report and follow its status online.') }}</p>
                </div>
                <div class="landing-hero-ribbon" data-hero-ribbon>
                    <p class="landing-hero-promise"><span>No account required</span><span>Private tracking link</span></p>
                    <div class="landing-hero-actions">
                        <a href="{{ route('public.report') }}" class="landing-hero-primary">{{ __('Report an incident') }}</a>
                        <a href="{{ route('track') }}" class="landing-hero-secondary">{{ __('Track your report') }}</a>
                    </div>
                </div>
            </section>

            @include('public.recent-news', ['news' => $footerNews])
        </main>
    </div>
@endsection
