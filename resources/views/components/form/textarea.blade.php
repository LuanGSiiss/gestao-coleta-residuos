@props([
    'name',
    'label',
    'value'    => null,
    'required' => false,
    'linhas'   => 3,
])

<div class="mb-3">
    <label for="{{ $name }}" class="form-label {{ $required ? 'obrigatorio' : '' }}">
        {{ $label }}
    </label>

    <textarea id="{{ $name }}" name="{{ $name }}" rows="{{ $linhas }}"
              {{ $attributes->class(['form-control', 'is-invalid' => $errors->has($name)]) }}
              @if($required) required @endif>{{ old($name, $value) }}</textarea>

    @error($name)
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>