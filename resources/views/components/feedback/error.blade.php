@props([
    'message' => '',
    'dismissible' => true,
    'autoDismiss' => 0,
])

@if (filled($message))
    <div class="feedback-banner feedback-banner-error is-entering is-shaking" role="alert" data-feedback-banner data-auto-dismiss="{{ (int) $autoDismiss }}">
        <span class="feedback-banner-icon" aria-hidden="true">!</span>
        <div class="feedback-banner-body">
            <p>{{ $message }}</p>
        </div>
        @if ($dismissible)
            <button type="button" class="feedback-banner-close" data-feedback-dismiss aria-label="Dismiss notification">×</button>
        @endif
    </div>
@endif
