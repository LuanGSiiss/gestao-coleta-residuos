@props(['dias'])

<span class="d-inline-flex flex-wrap gap-1" aria-hidden="true">
    @foreach (\App\Enums\DiaSemana::cases() as $dia)
        <span class="badge {{ in_array($dia, $dias, true) ? 'text-bg-primary' : 'text-body-tertiary border fw-normal' }}" title="{{ $dia->rotulo() }}">{{ $dia->abreviado() }}</span>
    @endforeach
</span>
<span class="visually-hidden">{{ implode(', ', array_map(fn ($dia) => $dia->rotulo(), $dias)) }}</span>