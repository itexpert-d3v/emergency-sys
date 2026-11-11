<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    @include('partials.theme-init')
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('Report an incident') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="public-surface public-ambulance-surface min-h-screen bg-slate-50 text-slate-800 antialiased transition-colors duration-200 dark:bg-[#18181b] dark:text-slate-100" style="background-image:url('{{ asset('assets/ambulance.jpg') }}');background-size:cover;background-position:center;background-attachment:fixed">
    <div class="app-photo-canvas min-h-screen">
        @include('public.navigation')
        <div class="mx-auto max-w-6xl px-4 py-6 sm:px-6 lg:px-8">
            <header class="civic-panel mb-5 grid gap-5 rounded-[12px] p-5 dark:bg-slate-950/95 dark:shadow-black/30 lg:grid-cols-[minmax(16rem,0.7fr)_minmax(0,1.3fr)] lg:items-center">
                <div>
                    <h1 class="text-2xl font-black tracking-tight text-slate-900 dark:text-white">{{ __('Report an incident') }}</h1>
                    <p class="mt-3 text-sm leading-6 text-slate-600 dark:text-slate-300">
                        {{ __('Share the key details and a responder can review the report quickly.') }}
                    </p>
                </div>
                <div class="border-t border-slate-200 pt-4 dark:border-slate-700 lg:border-l lg:border-t-0 lg:pl-5 lg:pt-0">
                    <p class="text-[10px] font-black uppercase tracking-[0.22em] text-slate-500 dark:text-slate-400">{{ __('Before you submit') }}</p>
                    <ul class="mt-3 grid gap-2 text-sm text-slate-600 sm:grid-cols-3 dark:text-slate-300">
                        <li>• {{ __('Describe what happened clearly and with facts.') }}</li>
                        <li>• {{ __('Include the nearest location or landmark.') }}</li>
                        <li>• {{ __('Use the tracking link to follow updates after submission.') }}</li>
                    </ul>
                </div>
            </header>

            <main class="civic-panel rounded-[12px] p-5 dark:bg-slate-950/95 dark:shadow-black/30 sm:p-7">
                <div id="form-alert" class="hidden rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-800/60 dark:bg-amber-950/30 dark:text-amber-200" role="alert" aria-live="polite"></div>

                {{-- novalidate: every error is shown inline by report-form.js, in one consistent style --}}
                <form id="report-form" action="{{ route('public.report.store') }}" method="post" enctype="multipart/form-data" novalidate class="mt-4 space-y-5">
                    @csrf
                    <input type="hidden" name="idempotency_key" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
                    <input type="hidden" name="language" value="{{ app()->getLocale() }}">

                    {{-- Restores typed values and shows field errors if the page was reloaded by the server --}}
                    <script>
                        window.reportFormState = @json(['errors' => $errors->messages(), 'old' => old()]);
                    </script>

                    <fieldset class="space-y-4" aria-labelledby="incident-heading">
                        <legend id="incident-heading" class="flex flex-col gap-3 text-lg font-black text-slate-900 sm:flex-row sm:items-center sm:justify-between dark:text-white"><span>{{ __('Report details') }}</span><span class="w-full max-w-xs"><span data-report-progress-label class="block text-right text-xs font-semibold text-slate-500 dark:text-slate-400">0 of 5 required details complete</span><span class="mt-1 block h-2 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700" role="progressbar" aria-label="Report completion" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"><span data-report-progress-bar class="block h-full w-0 rounded-full bg-blue-800 transition-[width] duration-200 dark:bg-blue-400"></span></span></span></legend>
                        <div>
                            <label for="category_id" class="mb-2 block text-sm font-semibold text-slate-700 dark:text-slate-200">{{ __('Incident type') }}</label>
                            <select id="category_id" name="category_id" title="Choose the type of emergency you are reporting." class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-blue-800 focus:ring-4 focus:ring-blue-100 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 dark:focus:border-blue-400 dark:focus:ring-blue-900/60" required>
                                <option value="">{{ __('Select an incident type') }}</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category['id'] }}" @selected(old('category_id') == $category['id'])>{{ $category['name'] }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="description" class="mb-2 block text-sm font-semibold text-slate-700 dark:text-slate-200">{{ __('What happened?') }}</label>
                            <textarea id="description" name="description" rows="8" minlength="20" maxlength="2000" title="Describe what happened, what is affected, and any immediate danger. At least 20 characters." class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-blue-800 focus:ring-4 focus:ring-blue-100 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 dark:focus:border-blue-400 dark:focus:ring-blue-900/60" placeholder="{{ __('Describe what happened, who or what is affected, and the help needed.') }}" required>{{ old('description') }}</textarea>
                            <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">{{ __('Include the facts that help the team understand the situation quickly.') }}</p>
                        </div>
                    </fieldset>

                    <fieldset class="space-y-4 border-t border-slate-200 pt-4 dark:border-slate-700" aria-labelledby="location-heading">
                        <legend id="location-heading" class="text-lg font-black text-slate-900 dark:text-white">{{ __('Location') }}</legend>
                        <div>
                            <label for="location_text" class="mb-2 block text-sm font-semibold text-slate-700 dark:text-slate-200">{{ __('Location') }}</label>
                            <input id="location_text" name="location_text" type="text" value="{{ old('location_text') }}" title="Enter a barangay, street, landmark, or nearby location. You can use GPS instead." class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-blue-800 focus:ring-4 focus:ring-blue-100 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 dark:focus:border-blue-400 dark:focus:ring-blue-900/60" placeholder="{{ __('Barangay / landmark / street name') }}">
                            <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">{{ __('A nearby landmark helps responders find the location.') }}</p>
                        </div>
                        <input id="latitude" name="latitude" type="hidden" value="{{ old('latitude') }}">
                        <input id="longitude" name="longitude" type="hidden" value="{{ old('longitude') }}">
                        <input id="location_accuracy" name="location_accuracy" type="hidden" value="{{ old('location_accuracy') }}">
                        <div class="rounded-xl bg-slate-50 p-3 dark:bg-slate-800/70">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div>
                                    <p class="font-bold text-slate-900 dark:text-slate-100">{{ __('Add one supporting photo') }}</p>
                                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('Optional. JPG, PNG, or WebP. Large photos are resized and converted to WebP automatically. Avoid images that identify victims.') }}</p>
                                </div>
                            </div>
                            <input id="photo" name="photo" type="file" accept="image/jpeg,image/png,image/webp" class="mt-4 block w-full text-sm text-slate-700 file:mr-4 file:min-h-10 file:border-0 file:bg-blue-800 file:px-4 file:py-2 file:font-bold file:text-white dark:text-slate-200 dark:file:bg-blue-400 dark:file:text-slate-950">
                        </div>
                    </fieldset>

                    <fieldset class="space-y-4 border-t border-slate-200 pt-4 dark:border-slate-700" aria-labelledby="contact-heading">
                        <legend id="contact-heading" class="text-lg font-black text-slate-900 dark:text-white">{{ __('Your contact') }}</legend>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="reporter_name" class="mb-2 block text-sm font-semibold text-slate-700 dark:text-slate-200">{{ __('Reporter name') }}</label>
                                <input id="reporter_name" name="reporter_name" type="text" value="{{ old('reporter_name') }}" title="Enter the name responders can use to contact you." class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-blue-800 focus:ring-4 focus:ring-blue-100 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 dark:focus:border-blue-400 dark:focus:ring-blue-900/60" placeholder="{{ __('Full name') }}" required>
                            </div>
                            <div>
                                <label for="contact_number" class="mb-2 block text-sm font-semibold text-slate-700 dark:text-slate-200">{{ __('Contact number') }}</label>
                                <input id="contact_number" name="contact_number" type="tel" value="{{ old('contact_number') }}" title="Enter a Philippine phone number starting with +63, for example +639171234567." class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-blue-800 focus:ring-4 focus:ring-blue-100 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 dark:focus:border-blue-400 dark:focus:ring-blue-900/60" placeholder="{{ __('+63...') }}" required>
                            </div>
                        </div>

                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 dark:border-slate-700 dark:bg-slate-800/70">
                            <label class="flex items-center gap-3 text-sm font-semibold text-slate-700 dark:text-slate-200">
                                <input id="reporting_for_other" name="reporting_for_other" type="checkbox" value="1" @checked(old('reporting_for_other')) class="h-4 w-4 rounded border-slate-300 text-blue-800 focus:ring-blue-800 dark:border-slate-600 dark:bg-slate-900" />
                                {{ __('I am reporting for someone else') }}
                            </label>

                            <div id="other-person-fields" class="mt-4 space-y-4">
                                <div>
                                    <label for="relationship" class="mb-2 block text-sm font-semibold text-slate-700 dark:text-slate-200">{{ __('Relationship to the person') }}</label>
                                    <input id="relationship" name="relationship" type="text" maxlength="80" value="{{ old('relationship') }}" title="Describe how you know the person you are reporting for." class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-blue-800 focus:ring-4 focus:ring-blue-100 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 dark:focus:border-blue-400 dark:focus:ring-blue-900/60" placeholder="{{ __('Parent / neighbor / coworker') }}">
                                </div>

                                <label class="flex items-center gap-3 text-sm font-semibold text-slate-700 dark:text-slate-200">
                                    <input id="victim_contactable" name="victim_contactable" type="checkbox" value="1" @checked(old('victim_contactable')) class="h-4 w-4 rounded border-slate-300 text-blue-800 focus:ring-blue-800 dark:border-slate-600 dark:bg-slate-900" />
                                    {{ __('The person can be contacted directly') }}
                                </label>
                            </div>
                        </div>

                        @if(config('services.turnstile.site_key'))
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3 dark:border-slate-700 dark:bg-slate-800/60">
                                <div class="cf-turnstile" data-sitekey="{{ config('services.turnstile.site_key') }}"></div>
                            </div>
                        @endif
                    </fieldset>

                    <div class="flex flex-col gap-3 border-t border-slate-200 pt-4 dark:border-slate-700 sm:flex-row sm:items-center sm:justify-between">
                        <button type="submit" class="inline-flex min-h-11 items-center justify-center rounded-[4px] bg-blue-800 px-6 py-3 text-sm font-black text-white transition hover:bg-blue-900 disabled:opacity-60 dark:bg-blue-400 dark:text-slate-950 dark:hover:bg-blue-300">{{ __('Submit a report') }}</button>
                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('Your information is only used to respond to this report.') }}</p>
                    </div>
                </form>
            </main>
        </div>
        @include('public.report-flow')
        @include('public.footer')
    </div>
</body>
</html>