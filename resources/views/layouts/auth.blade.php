@extends('layouts.base')

@section('robots', 'noindex,nofollow')
@section('body-class', 'auth-surface min-h-screen antialiased')

@section('body')
    <main class="auth-shell">
        @yield('content')
    </main>
@endsection
