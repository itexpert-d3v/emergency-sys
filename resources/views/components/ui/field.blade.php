@props(['for', 'label', 'error' => null, 'hint' => null])

<div class="ui-field">
    <label class="ui-label" for="{{ $for }}">{{ $label }}</label>
    {{ $slot }}
    @if ($hint)
        <p class="ui-hint">{{ $hint }}</p>
    @endif
    @if ($error)
        <p class="ui-error" role="alert">{{ $error }}</p>
    @endif
</div>
