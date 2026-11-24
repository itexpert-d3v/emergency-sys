@props(['as' => 'section'])

<{{ $as }} {{ $attributes->class(['ui-card']) }}>
    {{ $slot }}
</{{ $as }}>
