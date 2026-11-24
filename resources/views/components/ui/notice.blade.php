@props(['tone' => 'info', 'role' => null])

<div {{ $attributes->class(['ui-notice', 'ui-notice--' . $tone]) }} role="{{ $role ?? ($tone === 'danger' ? 'alert' : 'status') }}">
    {{ $slot }}
</div>
