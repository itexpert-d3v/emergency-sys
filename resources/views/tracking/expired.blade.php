<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Tracking expired') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="public-surface public-ambulance-surface min-h-screen bg-slate-50 text-slate-800 antialiased transition-colors duration-200 dark:bg-[#18181b] dark:text-slate-100" style="background-image:url('{{ asset('assets/ambulance.jpg') }}');background-size:cover;background-position:center;background-attachment:fixed">
    <div class="app-photo-canvas flex min-h-screen flex-col">
        @include('public.navigation')
        <main class="flex flex-1 items-center justify-center px-4 py-10">
        <section class="civic-panel w-full max-w-lg rounded-[6px] p-8 backdrop-blur dark:bg-slate-950/95">
            <p class="text-xs font-black uppercase tracking-[0.24em] text-blue-800 dark:text-blue-300">MDRRMO · {{ __('Report tracking') }}</p>
            <h1 class="mt-4 text-3xl font-black text-slate-900 dark:text-white">{{ __('Tracking expired') }}</h1>
            <p class="mt-3 leading-7 text-slate-600 dark:text-slate-300">{{ $message }}</p>
            <a href="{{ route('landing') }}" class="mt-6 inline-flex rounded-full bg-blue-800 px-5 py-2.5 text-sm font-black text-white hover:bg-blue-900 dark:bg-blue-400 dark:text-slate-950 dark:hover:bg-blue-300">{{ __('Back to home') }}</a>
        </section>
        </main>
        @include('public.footer')
    </div>
</body>
</html>
