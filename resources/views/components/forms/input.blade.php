{{-- Inputs tipo text, date, number... --}}

@props([
    'name',
    'label' => null,
    'type' => 'text',
    'value' => '',
    'required' => false,
    'readonly' => false,
    'col' => 'col-md-6',
])

<div class="form-group {{ $col }}">
    @if ($label)
        <label for="{{ $name }}" class="form-label text-start">{{ $label }}</label>
    @endif

    <input
        type="{{ $type }}"
        id="{{ $name }}"
        name="{{ $name }}"
        value="{{ old($name, $value) }}"
        class="form-control text-muted @error($name) is-invalid @enderror"
        @if ($required) required @endif
        @if ($readonly) readonly @endif
        {{ $attributes }}
    >

    @error($name)
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
