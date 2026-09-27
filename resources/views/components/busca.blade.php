@props([
    'rota',
    'placeholder' => 'Buscar…'
])

<form method="GET" action="{{ $rota }}" class="mb-3">
    <div class="input-group">
        <input type="search" name="busca" value="{{ request('busca') }}" class="form-control" placeholder="{{ $placeholder }}" aria-label="{{ $placeholder }}">

        <button type="submit" class="btn btn-outline-secondary">Buscar</button>

        @if (request('busca'))
            <a href="{{ $rota }}" class="btn btn-outline-secondary">Limpar</a>
        @endif
    </div>
</form>