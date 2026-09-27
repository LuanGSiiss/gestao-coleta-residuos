@props([
    'name',
    'label',
    'opcoes',
    'value'    => null,
    'required' => false,
    'vazio'    => 'Selecione…',
])

<div class="mb-3">
    <label for="{{ $name }}" class="form-label {{ $required ? 'obrigatorio' : '' }}">{{ $label }}</label>

    <select id="{{ $name }}" name="{{ $name }}"
            {{ $attributes->class(['form-select', 'is-invalid' => $errors->has($name)]) }}
            @if($required) required @endif>

        @if ($vazio)
            <option value="">{{ $vazio }}</option>
        @endif

        @foreach ($opcoes as $valor => $rotulo)
            <option value="{{ $valor }}"
                @selected((string) old($name, $value) === (string) $valor)>
                {{ $rotulo }}
            </option>
        @endforeach
    </select>

    @error($name)
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>