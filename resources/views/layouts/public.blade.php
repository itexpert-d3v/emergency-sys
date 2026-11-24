@extends('layouts.base')

@section('body-class', 'public-surface min-h-screen antialiased')

@section('body')
    <div class="app-photo-canvas min-h-screen">
        @include('public.navigation')
        @yield('content')
        @include('public.footer')
    </div>
@endsection
