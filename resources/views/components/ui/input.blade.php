@props(['id' => null, 'name' => null, 'type' => 'text', 'value' => null, 'invalid' => false, 'oldInput' => true])

<input
    @if ($id) id="{{ $id }}" @endif
    @if ($name) name="{{ $name }}" @endif
    type="{{ $type }}"
    @if ($name && $oldInput && !in_array($type, ['file', 'checkbox', 'radio', 'hidden', 'password'], true)) value="{{ old($name, $value) }}" @endif
    @if ($invalid) aria-invalid="true" @endif
    {{ $attributes->class(['ui-input', 'is-invalid' => $invalid]) }}
>
