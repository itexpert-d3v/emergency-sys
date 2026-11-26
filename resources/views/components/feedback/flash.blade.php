@props([
    'position' => 'top-right',
])

@php
    $messages = [];

    if (session('status')) {
        $messages[] = ['type' => 'success', 'message' => (string) session('status')];
    }

    if ($errors->any()) {
        $messages[] = ['type' => 'error', 'message' => $errors->first()];
    }
@endphp

@if (count($messages))
    <div class="feedback-stack feedback-stack-{{ $position }}" aria-live="polite">
        @foreach ($messages as $message)
            @if ($message['type'] === 'error')
                <x-feedback.error :message="$message['message']" />
            @else
                <x-feedback.success :message="$message['message']" />
            @endif
        @endforeach
    </div>
@endif
