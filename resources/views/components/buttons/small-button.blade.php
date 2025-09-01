{{-- Botones pequeños para tabla de empleados --}}

@props([
    'type' => 'button',
    'id' => null,
    'icon' => null,
    'text' => '',
    'variant' => 'smallPrimary',
    'class' => '',
])

@php
    $classes = match ($variant) {
        'smallPrimary' => 'btn btnAzlo-dark',
        'outline-danger' => 'btn btn-outline-danger',
        'default' => 'btn btnAzlo-dark',
    };
@endphp

<button type="{{ $type }}"
        @if ($id)
            id="{{ $id }}"
        @endif
        {{ $attributes->merge(['class' => "$classes $class"]) }}>
        @if ($icon)
            <i class="{{ $icon }} " aria-hidden="true"></i>
        @endif
        {{ $text }}
</button>
