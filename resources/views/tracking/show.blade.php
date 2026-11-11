<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('Report tracking') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="public-surface public-ambulance-surface min-h-screen bg-slate-50 text-slate-800 antialiased transition-colors duration-200 dark:bg-[#18181b] dark:text-slate-100" style="background-image:url('{{ asset('assets/ambulance.jpg') }}');background-size:cover;background-position:center;background-attachment:fixed">
    <div class="app-photo-canvas min-h-screen">
        @include('public.navigation')
        <main class="mx-auto max-w-3xl px-4 py-8">
            @php
                $trackingUrl = url('/track?reference=' . urlencode($reference_code) . '&token=' . urlencode($tracking_token ?? ''));
            @endphp

            {{-- data-reference and data-token are read by the status-refresh JS. Keep them. --}}
            <header class="civic-panel mb-6 mt-5 rounded-[6px] p-6 sm:p-8" data-status-widget data-reference="{{ $reference_code }}" data-token="{{ $tracking_token ?? '' }}">
                <p class="text-xs font-black uppercase tracking-[0.22em] text-blue-800 dark:text-blue-300">{{ __('Report tracking') }}</p>
                <h1 class="mt-3 text-3xl font-black tracking-tight text-slate-950 dark:text-white">{{ __('Your tracking link') }}</h1>
                <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">{{ __('Save this link. It is the only way to follow your report and message staff.') }}</p>

                <div class="mt-3 flex flex-col gap-2 sm:flex-row">
                    <input id="tracking-link" type="text" readonly value="{{ $trackingUrl }}"
                           class="w-full rounded-2xl border border-slate-300 bg-white px-3 py-2 font-mono text-xs text-slate-700 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200"
                           onclick="this.select()">
                    <button type="button" id="copy-tracking-link"
                            class="rounded-full bg-blue-800 px-4 py-2 text-sm font-bold text-white hover:bg-blue-900 dark:bg-blue-400 dark:text-slate-950">
                        {{ __('Copy link') }}
                    </button>
                </div>

                <div id="report-status" class="mt-5 inline-flex rounded-full bg-amber-100 px-4 py-1.5 text-sm font-black text-amber-900 dark:bg-amber-500/15 dark:text-amber-300">{{ __(ucfirst(str_replace('_', ' ', $status ?? 'submitted'))) }}</div>
                @if (!empty($has_photo))
                    <div class="mt-5"><p class="mb-2 text-xs font-bold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">{{ __('Attached photo') }}</p><a href="{{ route('track.photo', $tracking_token) }}" target="_blank" rel="noreferrer" class="inline-flex"><img src="{{ route('track.photo', $tracking_token) }}" alt="{{ __('Photo submitted with this report') }}" class="max-h-64 rounded-2xl object-contain shadow-sm"></a></div>
                @endif
            </header>

            <section class="grid gap-4 sm:grid-cols-2">
                <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900"><h2 class="text-xs font-black uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">{{ __('Submitted') }}</h2><p class="mt-3 font-bold text-slate-900 dark:text-white">{{ $submitted_at ? $submitted_at->format('F j, Y g:i A') : __('Unknown') }}</p></article>
                <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900"><h2 class="text-xs font-black uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">{{ __('Expiry countdown') }}</h2><p class="mt-3 font-bold text-slate-900 dark:text-white">{{ $expires_at ? __('Expires on :date', ['date' => \Illuminate\Support\Carbon::parse($expires_at)->format('F j, Y g:i A')]) : __('No expiry set.') }}</p>@if ($expires_at)<p data-expiry-countdown data-expires-at="{{ \Illuminate\Support\Carbon::parse($expires_at)->toISOString() }}" class="mt-2 text-sm font-semibold text-blue-800 dark:text-blue-300"></p>@endif</article>
            </section>

            <section class="mt-4 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                <h2 class="text-xl font-black text-slate-900 dark:text-white">{{ __('Status timeline') }}</h2>
                <p id="status-refresh" class="mt-2 text-xs text-slate-500 dark:text-slate-400" aria-live="polite">{{ __('Status updates automatically.') }}</p>
                <ol data-status-timeline class="mt-5 space-y-4 border-l-2 border-blue-100 pl-5 dark:border-slate-700">
                    @foreach ($timeline as $entry)
                        <li class="relative"><span class="absolute -left-[1.65rem] top-1 h-3 w-3 rounded-full bg-amber-400 ring-4 ring-amber-100 dark:ring-amber-500/20"></span><p class="font-bold text-slate-900 dark:text-white">{{ __($entry['label']) }}</p><p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $entry['timestamp'] ? \Illuminate\Support\Carbon::parse($entry['timestamp'])->format('F j, Y g:i A') : '' }}</p></li>
                    @endforeach
                </ol>
            </section>

            <section class="mt-4 grid gap-4 sm:grid-cols-2">
                <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900"><h2 class="font-black text-slate-900 dark:text-white">{{ __('Response updates') }}</h2><ol data-public-updates class="mt-3 space-y-3 text-sm leading-6 text-slate-600 dark:text-slate-300">@forelse ($public_updates as $update)<li><p class="font-semibold text-slate-800 dark:text-slate-100">{{ $update['body'] }}</p><p class="text-xs text-slate-500 dark:text-slate-400">{{ $update['created_at'] ? \Illuminate\Support\Carbon::parse($update['created_at'])->format('F j, Y g:i A') : '' }}</p></li>@empty<li data-public-updates-empty>{{ __('No response updates yet.') }}</li>@endforelse</ol></article>

                <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900" data-message-panel data-token="{{ $tracking_token ?? '' }}" data-closed="{{ ($thread_closed ?? false) ? 'true' : 'false' }}">
                    <h2 class="font-black text-slate-900 dark:text-white">{{ __('Message thread') }}</h2>
                    <ol data-message-list class="mt-4 max-h-72 space-y-3 overflow-y-auto" aria-live="polite"></ol>
                    <p data-message-empty class="mt-3 text-sm text-slate-500 dark:text-slate-400">{{ __('No messages yet.') }}</p>
                    <p data-message-alert class="mt-3 hidden rounded-xl bg-amber-50 px-3 py-2 text-sm text-amber-900 dark:bg-amber-500/10 dark:text-amber-200" role="status"></p>
                    {{-- onsubmit="return false" is a safety net: if the JS fails to load, the form can never
                         navigate to /track?body=... and wipe the token. messaging.js still handles the submit. --}}
                    <form data-message-form onsubmit="return false;" class="mt-4 space-y-3">
                        <label for="reporter-message" class="block text-sm font-bold text-slate-700 dark:text-slate-200">{{ __('Send a message to staff') }}</label>
                        <textarea id="reporter-message" name="body" rows="3" maxlength="500" title="Send staff a follow-up or additional details about your report." required class="w-full rounded-2xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-800 outline-none focus:border-blue-800 focus:ring-2 focus:ring-blue-800/15 dark:border-slate-600 dark:bg-slate-800 dark:text-white" placeholder="{{ __('Type your message') }}"></textarea>
                        <button type="submit" class="rounded-full bg-blue-800 px-4 py-2 text-sm font-bold text-white hover:bg-blue-900 disabled:opacity-60 dark:bg-blue-400 dark:text-slate-950 dark:hover:bg-blue-300">{{ __('Send message') }}</button>
                    </form>
                </article>
            </section>

            <a href="{{ route('landing') }}" class="mt-6 inline-flex rounded-full border border-slate-300 bg-white px-5 py-2.5 text-sm font-bold text-slate-700 hover:border-blue-800 hover:text-blue-800 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">← {{ __('Back to home') }}</a>
        </main>
        @include('public.footer')
    </div>

    <script>
        document.getElementById('copy-tracking-link')?.addEventListener('click', async (e) => {
            const input = document.getElementById('tracking-link');
            try { await navigator.clipboard.writeText(input.value); }
            catch (err) { input.select(); document.execCommand('copy'); }
            e.currentTarget.textContent = @json(__('Copied!'));
        });
    </script>
</body>
</html>