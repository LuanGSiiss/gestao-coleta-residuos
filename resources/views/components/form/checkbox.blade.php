@props([
    'name',
    'label',
    'checked' => true
])

<div class="form-check mb-3">
    <input type="hidden" name="{{ $name }}" value="0">

    <input type="checkbox" id="{{ $name }}" name="{{ $name }}" value="1" class="form-check-input"
           @checked(old($name, $checked))>

    <label class="form-check-label" for="{{ $name }}">{{ $label }}</label>
</div>