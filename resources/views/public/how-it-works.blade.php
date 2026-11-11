<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head><meta charset="UTF-8">@include('partials.theme-init')<meta name="viewport" content="width=device-width, initial-scale=1.0"><title>How it works · Kalibo MDRRMO</title>@vite(['resources/css/app.css', 'resources/js/app.js'])</head>
<body class="public-surface min-h-screen bg-slate-50 text-slate-800 antialiased dark:bg-[#18181b] dark:text-slate-100" style="background-image:url('{{ asset('assets/lgu kalibo.jpg') }}');background-size:cover;background-position:center;background-attachment:fixed">
<div class="app-photo-canvas min-h-screen">@include('public.navigation')
    <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <header class="max-w-3xl border-b border-slate-300 pb-6 dark:border-slate-700"><h1 class="text-3xl font-black leading-tight text-slate-950 dark:text-white sm:text-4xl">How it works</h1><p class="mt-3 text-base leading-7 text-slate-700 dark:text-slate-200">A clear path to submit a report and return for recorded updates.</p></header>
        <ol class="mt-7 divide-y divide-slate-300 dark:divide-slate-700">
            <li class="grid gap-3 py-6 sm:grid-cols-[5rem_1fr]"><span class="text-2xl font-black text-blue-800 dark:text-blue-300">01</span><div><h2 class="text-xl font-black text-slate-950 dark:text-white">Report</h2><p class="mt-2 max-w-2xl text-sm leading-6 text-slate-700 dark:text-slate-200">Describe what happened and where. Include details that help staff review the report, and submit the form without creating an account.</p><a href="{{ route('public.report') }}" class="mt-3 inline-block text-sm font-bold text-blue-800 underline underline-offset-4 dark:text-blue-300">Start a report</a></div></li>
            <li class="grid gap-3 py-6 sm:grid-cols-[5rem_1fr]"><span class="text-2xl font-black text-blue-800 dark:text-blue-300">02</span><div><h2 class="text-xl font-black text-slate-950 dark:text-white">Keep your private tracking link</h2><p class="mt-2 max-w-2xl text-sm leading-6 text-slate-700 dark:text-slate-200">After submission, use the private link provided to return to your report. Do not share it publicly.</p></div></li>
            <li class="grid gap-3 py-6 sm:grid-cols-[5rem_1fr]"><span class="text-2xl font-black text-blue-800 dark:text-blue-300">03</span><div><h2 class="text-xl font-black text-slate-950 dark:text-white">Check updates</h2><p class="mt-2 max-w-2xl text-sm leading-6 text-slate-700 dark:text-slate-200">Review the status history and any messages recorded by staff through your tracking page.</p><a href="{{ route('track') }}" class="mt-3 inline-block text-sm font-bold text-blue-800 underline underline-offset-4 dark:text-blue-300">Track a report</a></div></li>
        </ol>
        <img src="{{ asset('assets/municipal-hall.jpg') }}" alt="Municipal hall of Kalibo, Aklan" loading="lazy" class="mt-8 aspect-[16/7] w-full border border-slate-300 object-cover dark:border-slate-700">
    </main>
    @include('public.footer')
</div></body></html>
