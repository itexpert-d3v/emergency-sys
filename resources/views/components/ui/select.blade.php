@props(['id' => null, 'name' => null, 'invalid' => false])

<select
    @if ($id) id="{{ $id }}" @endif
    @if ($name) name="{{ $name }}" @endif
    @if ($invalid) aria-invalid="true" @endif
    {{ $attributes->class(['ui-input', 'ui-select', 'is-invalid' => $invalid]) }}
>
    {{ $slot }}
</select>
