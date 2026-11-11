<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head><meta charset="UTF-8">@include('partials.theme-init')<meta name="viewport" content="width=device-width, initial-scale=1.0"><title>About · Kalibo MDRRMO</title>@vite(['resources/css/app.css', 'resources/js/app.js'])</head>
<body class="public-surface min-h-screen bg-slate-50 text-slate-800 antialiased dark:bg-[#18181b] dark:text-slate-100" style="background-image:url('{{ asset('assets/lgu kalibo.jpg') }}');background-size:cover;background-position:center;background-attachment:fixed">
<div class="app-photo-canvas min-h-screen">@include('public.navigation')
    <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="grid gap-8 border-b border-slate-300 pb-8 dark:border-slate-700 md:grid-cols-[1fr_0.9fr] md:items-center">
            <div><h1 class="max-w-2xl text-3xl font-black leading-tight text-slate-950 dark:text-white sm:text-4xl">About Kalibo MDRRMO</h1><p class="mt-4 max-w-2xl text-base leading-7 text-slate-700 dark:text-slate-200">This portal helps residents submit incident reports and follow recorded status updates. It also provides a place for authorized municipal officers to publish public advisories and notices.</p></div>
            <img src="{{ asset('assets/mddrmobg.jpg') }}" alt="Kalibo MDRRMO response team outside the municipal hall" class="aspect-[16/10] w-full border border-slate-300 object-cover dark:border-slate-700">
        </div>
        <section class="grid gap-8 py-8 md:grid-cols-2">
            <div><h2 class="text-xl font-black text-slate-950 dark:text-white">What this portal does</h2><p class="mt-3 text-sm leading-6 text-slate-700 dark:text-slate-200">Residents can report an incident without creating an account, then use a private tracking link to review status updates and communicate with staff.</p></div>
            <div><h2 class="text-xl font-black text-slate-950 dark:text-white">Important to know</h2><p class="mt-3 text-sm leading-6 text-slate-700 dark:text-slate-200">Submitting a report does not dispatch responders. For immediate danger, contact the appropriate emergency hotline directly.</p><a href="{{ route('public.report') }}" class="mt-4 inline-flex min-h-10 items-center rounded-[4px] bg-blue-800 px-4 py-2 text-sm font-black text-white hover:bg-blue-900 dark:bg-blue-400 dark:text-slate-950">Report an incident</a></div>
        </section>
        <section class="border-t border-slate-300 py-7 dark:border-slate-700"><h2 class="text-xl font-black text-slate-950 dark:text-white">Municipality of Kalibo</h2><img src="{{ asset('assets/municipal-hall.jpg') }}" alt="Municipal hall of Kalibo, Aklan" loading="lazy" class="mt-4 aspect-[16/7] w-full border border-slate-300 object-cover dark:border-slate-700"></section>
    </main>
    @include('public.footer')
</div></body></html>
