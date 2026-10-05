@props([
    'rota',
    'placeholder' => 'Buscar…'
])

<form method="GET" action="{{ $rota }}" class="row g-2 align-items-center mb-3">
    <div class="col-sm-2">
        <input type="number" name="id" value="{{ request('id') }}" min="1" class="form-control" placeholder="Código" aria-label="Filtrar pelo código">
    </div>

    <div class="col-sm">
        <input type="search" name="busca" value="{{ request('busca') }}" class="form-control" placeholder="{{ $placeholder }}" aria-label="{{ $placeholder }}">
    </div>

    {{-- Filtros específicos de cada listagem --}}
    @if ($slot->isNotEmpty())
        <div class="col-sm-3">
            {{ $slot }}
        </div>
    @endif

    <div class="col-auto">
        <button type="submit" class="btn btn-outline-secondary">Buscar</button>

        @if (collect(request()->except('page'))->filter()->isNotEmpty())
            <a href="{{ $rota }}" class="btn btn-outline-secondary">Limpar</a>
        @endif
    </div>
</form>