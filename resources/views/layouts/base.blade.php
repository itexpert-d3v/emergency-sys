<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#155f73">
    <meta name="robots" content="@yield('robots', 'index,follow')">
    @hasSection('description')
        <meta name="description" content="@yield('description')">
    @endif
    <link rel="icon" type="image/png" href="{{ asset('assets/sou.png') }}">
    @include('partials.theme-init')
    <title>@yield('title', config('app.name', 'Kalibo MDRRMO Emergency Response'))</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @yield('base-styles')
    @stack('head')
    @yield('head')
    @includeIf('partials.pwa-meta')
    @stack('styles')
    @yield('styles')
</head>
<body class="@yield('body-class', 'min-h-screen antialiased')" style="@yield('body-style')">
    @yield('body')
    @yield('scripts')
    @stack('scripts')
</body>
</html>
