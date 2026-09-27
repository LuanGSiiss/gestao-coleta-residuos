@props([
    'acao',
    'confirmacao'
])

<form method="POST" action="{{ $acao }}" class="d-inline" data-confirmacao="{{ $confirmacao }}" onsubmit="return confirm(this.dataset.confirmacao)">
    @csrf
    @method('DELETE')
    
    <button type="submit" class="btn btn-sm btn-outline-danger">Excluir</button>
</form>