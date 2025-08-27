{{-- Imputs tipo textarea --}}

@props([
    'name',
    'label' => null,
    'value' => '',
    'required' => false,
    'rows' => 2,
    'col' => 'col-md-12',
])

<div class="form-group {{ $col }}">
    @if ($label)
        <label for="{{ $name }}">{{ $label }}</label>
    @endif

    <textarea
        id="{{ $name }}"
        name="{{ $name }}"
        class="form-control text-muted @error($name) is-invalid @enderror"
        rows="{{ $rows }}"
        @if ($required) required @endif
        {{ $attributes }}
    >{{ old($name, $value) }}</textarea>

    @error($name)
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
