{{-- Botones con estilos según su uso --}}

@props([
    'type' => 'button',
    'id' => null,
    'icon' => null,
    'class' => '',
    'text' => '',
    'variant' => 'primary',
    'href' => null,
])

@php
    $classes = match ($variant) {
        'primary' => 'btn btnAzlo-dark btn-personal',
        'secondary' => 'btn btn-outline-secondary btn-personal',
        'secondary-light' => 'btn btn-outline-light btn-personal',
        'danger' => 'btn btn-outline-danger btn-personal',
        'default' => 'btn btnAzlo-outline btn-personal',
        'dark-azlo' => 'btn btnAzlo-dark btn-big',
    };
@endphp

@if ($href)
    <a href="{{ $href }}"
        @if ($id)
            id="{{ $id }}"
        @endif
        {{ $attributes->merge(['class' => "$classes $class"]) }}>
        @if ($icon)
            <i class="{{ $icon }} me-2" aria-hidden="true"></i>
        @endif
        {{ $text }}
    </a>
@else
    <button type="{{ $type }}"
        @if ($id)
            id="{{ $id }}"
        @endif
        {{ $attributes->merge(['class' => "$classes $class"]) }}>
        @if ($icon)
            <i class="{{ $icon }} me-2" aria-hidden="true"></i>
        @endif
        {{ $text }}
    </button>
@endif
