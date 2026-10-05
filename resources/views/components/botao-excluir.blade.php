@props([
    'acao',
    'confirmacao'
])

<form method="POST" action="{{ $acao }}" class="d-inline" data-confirmacao="{{ $confirmacao }}" data-confirmacao-acao="Excluir">
    @csrf
    @method('DELETE')
    
    <button type="submit" class="btn btn-sm btn-outline-danger">Excluir</button>
</form>