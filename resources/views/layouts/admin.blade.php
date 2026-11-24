@extends('layouts.base')

@section('robots', 'noindex,nofollow')
@section('body-class', 'operations-surface min-h-screen antialiased')

@section('body')
    <div class="operations-shell">
        @include('admin.sidebar')
        <main class="operations-main ops @yield('main-class')">
            @yield('content')
        </main>
    </div>
@endsection
