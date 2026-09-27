@props([
    'name',
    'label',
    'value'    => null,
    'type'     => 'text',
    'required' => false,
    'ajuda'    => null,
])

<div class="mb-3">
    <label for="{{ $name }}" class="form-label {{ $required ? 'obrigatorio' : '' }}">{{ $label }}</label>

    {{-- old() preserva o que foi digitado quando a validação falha --}}
    <input type="{{ $type }}" id="{{ $name }}" name="{{ $name }}" value="{{ old($name, $value) }}"
           {{ $attributes->class(['form-control', 'is-invalid' => $errors->has($name)]) }} 
           @if($required) required @endif>

    @if ($ajuda)
        <div class="form-text">{{ $ajuda }}</div>
    @endif

    @error($name)
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>