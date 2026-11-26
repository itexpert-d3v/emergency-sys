@extends('layouts.public')

@section('title', __('Report an incident'))
@section('body-class', 'public-surface public-ambulance-surface min-h-screen antialiased')
@section('body-style')
    background-image: url('{{ asset('assets/ambulance.jpg') }}');
    background-size: cover;
    background-position: center;
    background-attachment: fixed;
@endsection

@push('head')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
@endpush

@section('content')
    <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
        <header class="civic-panel mb-6 grid gap-5 rounded-[12px] p-5 lg:grid-cols-[minmax(16rem,0.7fr)_minmax(0,1.3fr)] lg:items-center">
            <div>
                <h1 class="text-2xl font-black tracking-tight text-[var(--text)]">{{ __('Report an incident') }}</h1>
                <p class="mt-3 text-sm leading-6 text-[var(--text-muted)]">
                    {{ __('Share the key details and a responder can review the report quickly.') }}
                </p>
            </div>
            <div class="border-t border-[var(--border)] pt-4 lg:border-l lg:border-t-0 lg:pl-5 lg:pt-0">
                <p class="text-[10px] font-black uppercase tracking-[0.22em] text-[var(--text-muted)]">{{ __('Before you submit') }}</p>
                <ul class="mt-3 grid gap-2 text-sm text-[var(--text-muted)] sm:grid-cols-3">
                    <li>• {{ __('Describe what happened clearly and with facts.') }}</li>
                    <li>• {{ __('Include the nearest location or landmark.') }}</li>
                    <li>• {{ __('Use the tracking link to follow updates after submission.') }}</li>
                </ul>
            </div>
        </header>

        <main class="civic-panel rounded-[12px] p-5 sm:p-7">
            <x-feedback.flash position="inline" />

            <form id="report-form" action="{{ route('public.report.store') }}" method="post" enctype="multipart/form-data" class="mt-5 space-y-6" novalidate>
                @csrf
                <input type="hidden" name="idempotency_key" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
                <input type="hidden" name="language" value="{{ app()->getLocale() }}">

                <fieldset class="space-y-5" aria-labelledby="incident-heading">
                    <legend id="incident-heading" class="flex flex-col gap-3 text-lg font-black text-[var(--text)] sm:flex-row sm:items-center sm:justify-between">
                        <span>{{ __('Report details') }}</span>
                        <span class="w-full max-w-xs">
                            <span data-report-progress-label class="block text-right text-xs font-semibold text-[var(--text-muted)]">0 of 4 required details complete</span>
                            <span class="mt-1 block h-2 overflow-hidden rounded-full bg-[var(--surface-raised)]" role="progressbar" aria-label="Report completion" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">
                                <span data-report-progress-bar class="block h-full w-0 rounded-full bg-[var(--accent)] transition-[width] duration-200"></span>
                            </span>
                        </span>
                    </legend>

                    <div>
                        <label for="description" class="mb-2 block text-sm font-semibold text-[var(--text)]">{{ __('What happened?') }}</label>
                        <x-ui.textarea id="description" name="description" rows="6" minlength="20" maxlength="2000" class="w-full" title="Describe what happened, what is affected, and where it occurred. At least 20 characters." placeholder="{{ __('Example: A man collapsed near the barangay hall around 2:30 PM. He is unconscious but breathing, and bystanders are trying to keep him comfortable.') }}" required />
                        <p class="mt-2 text-xs text-[var(--text-muted)]">{{ __('Include the facts that help the team understand the situation quickly.') }}</p>
                    </div>
                </fieldset>

                <fieldset class="space-y-6 border-t border-[var(--border)] pt-6" aria-labelledby="contact-heading">
                    <legend id="contact-heading" class="text-lg font-black text-[var(--text)]">{{ __('Your contact') }}</legend>
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div class="space-y-2">
                            <label for="reporter_name" class="block text-sm font-semibold text-[var(--text)]">{{ __('Reporter name') }}</label>
                            <x-ui.input id="reporter_name" name="reporter_name" type="text" pattern="[\p{L}\p{M}]+([ .'-]+[\p{L}\p{M}]+)*\.?'" title="Use letters only, with spaces, hyphens, apostrophes, or periods as separators." placeholder="{{ __('e.g. Juan Dela Cruz') }}" required />
                        </div>
                        <div class="space-y-2">
                            <label for="contact_number" class="block text-sm font-semibold text-[var(--text)]">{{ __('Contact number') }}</label>
                            <x-ui.input id="contact_number" name="contact_number" type="tel" pattern="\+63[0-9]{10,12}" title="Enter a Philippine phone number starting with +63, for example +639171234567." placeholder="{{ __('+63 917 123 4567') }}" required />
                            <p class="text-xs text-[var(--text-muted)]">{{ __('Include the +63 country code, e.g. +639171234567.') }}</p>
                        </div>
                    </div>

                    <div class="space-y-4 rounded-xl border border-[var(--border)] bg-[var(--surface-raised)] p-4 sm:p-5">
                        <input type="hidden" name="reporting_for_other" value="0">
                        <label class="flex items-start gap-3 text-sm font-semibold text-[var(--text)]">
                            <input id="reporting_for_other" name="reporting_for_other" type="checkbox" value="1" class="mt-0.5 h-4 w-4 rounded border-[var(--border)] text-[var(--accent)] focus:ring-[var(--focus)]" @checked(old('reporting_for_other')) />
                            <span>{{ __('I am reporting for someone else') }}</span>
                        </label>
                        <div id="other-person-fields" class="space-y-4 border-t border-[var(--border)] pt-4 @if(!old('reporting_for_other')) hidden @endif">
                            <div class="space-y-2">
                                <label for="relationship" class="block text-sm font-semibold text-[var(--text)]">{{ __('Relationship to the person') }}</label>
                                <x-ui.input id="relationship" name="relationship" type="text" maxlength="80" title="Describe how you know the person you are reporting for." placeholder="{{ __('e.g. Parent, neighbor, coworker') }}" value="{{ old('relationship') }}" />
                            </div>
                            <input type="hidden" name="victim_contactable" value="0">
                            <label class="flex items-start gap-3 text-sm font-semibold text-[var(--text)]">
                                <input id="victim_contactable" name="victim_contactable" type="checkbox" value="1" class="mt-0.5 h-4 w-4 rounded border-[var(--border)] text-[var(--accent)] focus:ring-[var(--focus)]" @checked(old('victim_contactable')) />
                                <span>{{ __('The person can be contacted directly') }}</span>
                            </label>
                        </div>
                    </div>

                    @if(config('services.turnstile.site_key'))
                        <div class="rounded-xl border border-[var(--border)] bg-[var(--surface-raised)] p-4"><div class="cf-turnstile" data-sitekey="{{ config('services.turnstile.site_key') }}"></div></div>
                    @endif
                </fieldset>

                <fieldset class="report-location-section space-y-6 border-t border-[var(--border)] pt-6" aria-labelledby="location-heading">
                    <legend id="location-heading" class="text-lg font-black text-[var(--text)]">{{ __('Location') }}</legend>

                    <div class="report-location-grid items-start">
                        {{-- Map column --}}
                        <div class="report-location-map">
                            <div class="mb-3 flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
                                <p class="text-sm font-semibold text-[var(--text)]">{{ __('Pin your exact location') }} <span class="font-normal text-[var(--text-muted)]">(optional)</span></p>
                                <button type="button" id="locate-btn" class="report-locate-btn">
                                    <span id="locate-btn-text">{{ __('Use my current location') }}</span>
                                </button>
                            </div>
                            <div id="map" class="border border-[var(--border)]" data-tile-url="{{ config('maps.tile_url') }}" data-tile-attribution="{{ config('maps.tile_attribution') }}" data-tile-key="{{ config('maps.tile_key') }}"></div>
                            <p id="pin-state" class="report-pin-state" data-set="false">No pin set — responders will rely on the landmark text.</p>
                            <p id="location-status" class="report-location-status" role="status" aria-live="polite"></p>
                            <p id="pin-live-region" class="sr-only" aria-live="polite"></p>
                        </div>

                        {{-- Details column – full-width location input + photo --}}
                        <div class="report-location-details space-y-5">
                            <div class="space-y-2">
                                <label for="location_text" class="block text-sm font-semibold text-[var(--text)]">{{ __('Landmark / barangay / street (optional)') }}</label>
                                <x-ui.input id="location_text" name="location_text" type="text" title="Enter a barangay, street, landmark, or nearby location. You can use GPS instead." placeholder="{{ __('e.g. Barangay San Isidro, near the covered court') }}" value="{{ old('location_text') }}" class="w-full" />
                                <p id="location-autofilled-note" class="hidden text-xs text-[var(--text-muted)]">{{ __('Address auto-filled from your GPS pin. You can edit it.') }}</p>
                                <p class="text-xs text-[var(--text-muted)]">{{ __('A nearby landmark helps responders find the location.') }}</p>
                            </div>

                            <input id="latitude" name="latitude" type="hidden" value="{{ old('latitude') }}">
                            <input id="longitude" name="longitude" type="hidden" value="{{ old('longitude') }}">
                            <input id="location_accuracy" name="location_accuracy" type="hidden" value="{{ old('location_accuracy') }}">

                            <div class="report-photo-section">
                                <input id="photo" name="photo" type="file" accept="image/jpeg,image/png,image/webp" class="sr-only" tabindex="0">
                                <div data-photo-dropzone role="button" tabindex="0" aria-label="Add an optional supporting photo" class="report-photo-dropzone">
                                    <span class="report-photo-icon" aria-hidden="true">＋</span>
                                    <p>{{ __('Add a supporting photo') }}</p>
                                    <small>JPG, PNG, or WebP · up to 4 MB</small>
                                </div>
                                <div data-photo-preview class="report-photo-preview hidden">
                                    <img data-photo-preview-image src="" alt="Selected supporting photo preview">
                                    <div class="min-w-0"><p data-photo-preview-name class="truncate font-bold text-[var(--text)]"></p><p data-photo-preview-size class="text-xs text-[var(--text-muted)]"></p></div>
                                    <button type="button" data-photo-remove class="report-photo-remove">Remove</button>
                                </div>
                                <p class="mt-2 text-xs text-[var(--text-muted)]">{{ __('Avoid images that identify victims.') }}</p>
                            </div>
                        </div>
                    </div>
                </fieldset>

                <div class="flex flex-col gap-4 border-t border-[var(--border)] pt-6 sm:flex-row sm:items-center sm:justify-between">
                    <button type="submit" id="report-submit-btn" data-shake-target class="report-submit-btn">
                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                        <span>{{ __('Submit a report') }}</span>
                    </button>
                    <p class="text-xs text-[var(--text-muted)]">{{ __('Your information is only used to respond to this report.') }}</p>
                </div>
            </form>
        </main>
    </div>
    @include('public.report-flow')
@endsection

@push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
@endpush
