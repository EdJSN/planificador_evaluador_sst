{{-- Imputs del tipo select --}}

@props(['name', 'label' => null, 'options' => [], 'selected' => null, 'required' => false, 'col' => 'col-md-3'])

<div class="form-group {{ $col }}">
    @if ($label)
        <label for="{{ $name }}">{{ $label }}</label>
    @endif

    <select id="{{ $attributes->get('id', $name) }}" name="{{ $name }}"
        {{ $attributes->merge([
            'class' => 'form-select text-muted ' . ($errors->has($name) ? 'is-invalid' : ''),
        ]) }}
        @if ($required) required @endif {{ $attributes }}>
        @foreach ($options as $value => $text)
            <option value="{{ $value }}" @selected(old($name, $selected) == $value)>
                {{ $text }}
            </option>
        @endforeach
    </select>

    @error($name)
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
