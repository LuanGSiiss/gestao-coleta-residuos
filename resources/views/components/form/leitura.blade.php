@props([
    'label',
    'valor'      => null,
    'multilinha' => false,
    'vazio'      => '—',
])

<div class="mb-3">
    <div class="form-label">{{ $label }}</div>

    <div {{ $attributes->class('campo-leitura') }}>
        @if ($slot->isNotEmpty())
            {{ $slot }}
        @elseif (filled($valor))
            <span class="{{ $multilinha ? 'texto-preservado' : '' }}">{{ $valor }}</span>
        @else
            <span class="text-body-secondary">{{ $vazio }}</span>
        @endif
    </div>
</div>