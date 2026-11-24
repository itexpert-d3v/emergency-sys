@props(['id' => null, 'name' => null, 'value' => null, 'invalid' => false])

<textarea
    @if ($id) id="{{ $id }}" @endif
    @if ($name) name="{{ $name }}" @endif
    @if ($invalid) aria-invalid="true" @endif
    {{ $attributes->class(['ui-input', 'ui-textarea', 'is-invalid' => $invalid]) }}
>{{ $name ? old($name, $value ?? $slot) : ($value ?? $slot) }}</textarea>
