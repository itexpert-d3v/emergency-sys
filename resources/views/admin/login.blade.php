<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="operations-surface admin-photo-surface min-h-screen bg-slate-50 text-slate-800 antialiased transition-colors duration-200 dark:bg-[#18181b] dark:text-slate-100" style="background-image:url('{{ asset('assets/bgkgroup.jpg') }}');background-size:cover;background-position:center;background-attachment:fixed">
    <div class="app-photo-canvas flex min-h-screen items-center justify-center px-4 py-10">
        <div class="admin-login-panel civic-panel grid w-full max-w-4xl overflow-hidden rounded-[6px] md:grid-cols-[0.9fr_1.1fr] dark:border-slate-700 dark:bg-slate-900">
            <div class="bg-slate-100 p-8 text-slate-800 md:p-12 dark:bg-slate-800 dark:text-slate-100">
                <div class="mb-7"><span class="brand-marks brand-marks-login"><img class="brand-mark-sou" src="{{ asset('assets/sou.png') }}" alt="Special Operations Unit emblem" width="52" height="52"><img class="brand-mark-kalibo" src="{{ asset('assets/kalibo-logo.png') }}" alt="Municipality of Kalibo seal" width="52" height="52"></span></div>
                <p class="text-xs font-black uppercase tracking-[0.18em] text-blue-800 dark:text-blue-300">Kalibo MDRRMO · SOU</p>
                <h1 class="mt-3 text-3xl font-black tracking-tight text-slate-950 dark:text-white">Administrator workspace</h1>
                <p class="mt-4 max-w-md leading-7 text-slate-600 dark:text-slate-300">A protected workspace for reviewing reports, publishing public updates, and keeping municipal response records accountable.</p>
                <div class="mt-8 border-t border-slate-300 pt-5 text-sm font-bold text-slate-700 dark:border-slate-600 dark:text-slate-200">
                    <p>Authorized access only</p>
                    <p class="mt-2 font-normal text-slate-600 dark:text-slate-300">Sign in with your MDRRMO administrator account to continue.</p>
                </div>
            </div>
            <div class="bg-white p-8 md:p-12 dark:bg-slate-900">
                <div class="mb-8 flex items-center justify-between">
                    <div><p class="text-xs font-black uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Secure sign in</p><h2 class="mt-2 text-2xl font-black text-slate-900 dark:text-white">Welcome back</h2></div>
                    <div class="flex items-center gap-1">
                        @include('components.theme-toggle', ['placement' => 'inline', 'iconOnly' => true, 'minimal' => true])
                        <a href="{{ route('landing') }}" aria-label="Back to home" title="Back to home" class="minimal-icon-control">
                            <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6M9 12h11"/></svg>
                        </a>
                    </div>
                </div>

                @if ($errors->any())
                    <div class="mb-6 rounded-xl border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-200">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.login') }}" class="space-y-5">
                    @csrf
                    <div>
                        <label for="email" class="mb-2 block text-sm font-bold text-slate-700 dark:text-slate-200">Administrator email</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" placeholder="you@example.gov.ph" title="Enter your administrator account email address." required autofocus class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-slate-900 outline-none focus:border-blue-800 focus:ring-2 focus:ring-blue-800/15 dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                    </div>

                    <div>
                        <label for="password" class="mb-2 block text-sm font-bold text-slate-700 dark:text-slate-200">Account password</label>
                        <input id="password" name="password" type="password" placeholder="Enter your password" title="Enter the password for your administrator account." required class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-slate-900 outline-none focus:border-blue-800 focus:ring-2 focus:ring-blue-800/15 dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                    </div>

                    <button type="submit" class="w-full rounded-full bg-blue-800 px-4 py-3 text-sm font-black text-white transition hover:bg-blue-900 dark:bg-blue-400 dark:text-slate-950 dark:hover:bg-blue-300">Continue to admin panel</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
