@extends('layouts.auth')

@section('title', 'Operations sign in · Kalibo MDRRMO')
@section('body-class', 'operations-surface admin-photo-surface auth-surface min-h-screen antialiased')

@push('styles')
<style>
        /* ------------------------------------------------------------------
           Adaptive Neumorphism Tokens — Gold & Blue-White (Light) / Black & Gold (Dark)
           ------------------------------------------------------------------ */
        :root {
            /* Light mode: warm white surface with gold accent */
            --nm-bg: #f7f7f5;
            --nm-hi: #ffffff;
            --nm-lo: #cfcfc7;
            
            --nm-ink: #18181b;
            --nm-ink-2: #52525b;
            --nm-ink-3: #71717a;
            
            --nm-accent: #d4af37;
            --nm-accent-ink: #18181b;
            --nm-red: #c0392b;
            --nm-border-subtle: rgba(0, 0, 0, 0.06);
            --nm-panel-inset: inset 1px 0 0 rgba(255, 255, 255, 0.6), inset 20px 0 40px rgba(0, 0, 0, 0.04);
            --nm-canvas-overlay-1: rgba(238, 242, 246, 0.92);
            --nm-canvas-overlay-2: rgba(224, 231, 238, 0.96);
        }

        /* Dark mode: black surface with gold accent — synchronized from the shared theme setting */
        :root.dark {
            --nm-bg: #121212;
            --nm-hi: #1e1e1e;
            --nm-lo: #0a0a0a;
            
            --nm-ink: #fffaf0;
            --nm-ink-2: #d8cbb0;
            --nm-ink-3: #b7a98d;
            
            --nm-accent: #d4af37;
            --nm-accent-ink: #121212;
            --nm-red: #e74c3c;
            --nm-border-subtle: rgba(212, 160, 23, 0.15);
            --nm-panel-inset: inset 1px 0 0 rgba(212, 160, 23, 0.06), inset 20px 0 40px rgba(0, 0, 0, 0.4);
            --nm-canvas-overlay-1: rgba(10, 10, 10, 0.94);
            --nm-canvas-overlay-2: rgba(5, 5, 5, 0.97);
        }

        /* Neumorphic shadow definitions (use variables above) */
        :root {
            --nm-raised: 8px 8px 18px var(--nm-lo), -8px -8px 18px var(--nm-hi);
            --nm-raised-sm: 4px 4px 10px var(--nm-lo), -4px -4px 10px var(--nm-hi);
            --nm-inset: inset 6px 6px 12px var(--nm-lo), inset -6px -6px 12px var(--nm-hi);
            --nm-inset-sm: inset 3px 3px 6px var(--nm-lo), inset -3px -3px 6px var(--nm-hi);
        }

        body {
            color: var(--nm-ink);
            font-family: var(--font-sans);
            margin: 0;
            overflow-x: hidden;
            background: var(--nm-bg);
        }

        .login-canvas {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            
            background-image: 
                linear-gradient(135deg, var(--nm-canvas-overlay-1), var(--nm-canvas-overlay-2)), 
                url('{{ asset('assets/mddrmobg.jpg') }}');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            position: relative;
        }

        /* Main Neumorphic Card */
        .nm-card {
            background: var(--nm-bg);
            border-radius: 32px;
            box-shadow: var(--nm-raised);
            overflow: hidden;
            display: grid;
            width: 100%;
            max-width: 1000px;
            grid-template-columns: 1fr;
            z-index: 10;
            position: relative;
        }
        
        @media (min-width: 850px) {
            .nm-card { grid-template-columns: 1.1fr 0.9fr; }
        }

        /* Left Side: Branding */
        .nm-brand-panel {
            padding: 3.5rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        /* Right Side: Form Panel (Slightly inset to separate it) */
        .nm-form-panel {
            padding: 3.5rem;
            background: var(--nm-bg);
            box-shadow: var(--nm-panel-inset);
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        /* Inputs (Carved into the surface) */
        .nm-input {
            width: 100%;
            background: var(--nm-bg);
            border: 0;
            border-radius: 14px;
            padding: 0.85rem 1rem;
            color: var(--nm-ink);
            font-size: 0.95rem;
            font-family: inherit;
            box-shadow: var(--nm-inset);
            transition: outline 0.2s ease;
        }
        .nm-input:focus {
            outline: 2px solid var(--nm-accent);
            outline-offset: 2px;
        }
        .nm-input::placeholder {
            color: var(--nm-ink-3);
            opacity: 0.8;
        }

        /* ------------------------------------------------------------------
           Animated Login Button
           ------------------------------------------------------------------ */
        .nm-btn-primary {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            min-height: 52px;
            padding: 0 1.5rem;
            border: 0;
            border-radius: 14px;
            background: var(--nm-accent);
            color: var(--nm-accent-ink);
            font-size: 0.95rem;
            font-weight: 800;
            letter-spacing: 0.02em;
            cursor: pointer;
            box-shadow: var(--nm-raised-sm);
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            overflow: hidden;
        }
        .nm-btn-primary:hover {
            filter: brightness(1.08);
            transform: translateY(-2px);
        }
        .nm-btn-primary:active {
            box-shadow: var(--nm-inset-sm);
            transform: scale(0.98);
        }

        /* Button contents wrapper for animation */
        .btn-content {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            width: 100%;
        }

        /* The Arrow */
        .btn-arrow {
            transition: transform 0.4s cubic-bezier(0.25, 0.8, 0.25, 1), opacity 0.4s ease;
        }

        /* Back Button (Icon only) */
        .nm-icon-btn {
            display: grid;
            place-items: center;
            height: 42px;
            width: 42px;
            border-radius: 12px;
            background: var(--nm-bg);
            color: var(--nm-ink-2);
            box-shadow: var(--nm-raised-sm);
            transition: all 0.2s ease-out;
            text-decoration: none;
        }
        .nm-icon-btn:hover {
            color: var(--nm-accent);
            transform: translateY(-2px);
        }
        .nm-icon-btn:active {
            box-shadow: var(--nm-inset-sm);
            transform: scale(0.95);
        }
    </style>
@endpush

@section('content')
    <div class="login-canvas">
        <div class="nm-card" id="login-card">
            <div class="nm-brand-panel">
                <div>
                    <div class="flex items-center gap-4 mb-8">
                        <img class="h-12 w-12 object-contain filter drop-shadow-[0_4px_6px_rgba(0,0,0,0.15)]" src="{{ asset('assets/sou.png') }}" alt="SOU">
                        <img class="h-12 w-12 object-contain filter drop-shadow-[0_4px_6px_rgba(0,0,0,0.15)]" src="{{ asset('assets/kalibo-logo.png') }}" alt="Kalibo">
                    </div>
                    <h1 class="text-4xl font-black tracking-tight mb-4" style="color: var(--nm-ink);">Operations<br>Command Center</h1>
                    <p class="text-sm leading-relaxed max-w-sm" style="color: var(--nm-ink-2);">A secure terminal for reviewing incident reports, deploying response units, and managing municipal communications.</p>
                </div>
                <div class="mt-12 pt-6" style="border-top: 1px solid var(--nm-lo);">
                    <p class="text-xs font-bold uppercase tracking-wider mb-1" style="color: var(--nm-ink);">Restricted Access</p>
                    <p class="text-xs leading-relaxed" style="color: var(--nm-ink-3);">Authentication is required. Unauthorized attempts are logged and monitored by MDRRMO security.</p>
                </div>
            </div>

            <div class="nm-form-panel">
                <div class="flex items-center justify-between mb-8">
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-widest mb-1" style="color: var(--nm-accent);">Identity Verification</p>
                        <h2 class="text-2xl font-black" style="color: var(--nm-ink);">Sign in</h2>
                    </div>
                    <a href="{{ route('landing') }}" title="Back to home" class="nm-icon-btn">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6M9 12h11"/></svg>
                    </a>
                </div>

                <x-feedback.flash position="inline" />

                <form method="POST" action="{{ route('login') }}" id="login-form" class="space-y-5">
                    @csrf
                    <div>
                        <label for="email" class="mb-2 block text-xs font-bold uppercase tracking-wide" style="color: var(--nm-ink-2);">Account Email</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" placeholder="admin@kalibo.gov.ph" required autofocus class="nm-input">
                    </div>
                    <div>
                        <label for="password" class="mb-2 block text-xs font-bold uppercase tracking-wide" style="color: var(--nm-ink-2);">Password</label>
                        <input id="password" name="password" type="password" placeholder="••••••••••••" required class="nm-input">
                    </div>
                    <button type="submit" id="login-btn" data-shake-target class="nm-btn-primary mt-6">
                        <div class="btn-content">
                            <span class="btn-text">Authenticate</span>
                            <svg class="btn-arrow h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        </div>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('login-form').addEventListener('submit', function() {
            document.getElementById('login-btn').classList.add('is-loading');
        });
    </script>
@endsection