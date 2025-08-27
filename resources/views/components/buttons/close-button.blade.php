{{-- Botón para cerrar mosales --}}

@props([
    'type' => 'button',
    'id' => null,
    'icon' => null,
    'text' => '',
])

<button type="{{ $type }}"
        @if ($id)
            id="{{ $id }}"
        @endif
        {{ $attributes->merge(['class' => 'btn-close']) }}>
        @if ($icon)
            <i class="{{ $icon }} " aria-hidden="true"></i>
        @endif
        {{ $text }}
</button>
