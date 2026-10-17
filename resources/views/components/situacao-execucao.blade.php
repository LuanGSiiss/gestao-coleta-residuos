@props(['execucao'])

<span class="badge text-bg-{{ $execucao->situacao->cor() }}" 
      @if ($execucao->motivo_cancelamento) title="{{ $execucao->motivo_cancelamento }}" @endif>
    {{ $execucao->situacao->rotulo() }}
</span>
@if ($execucao->atrasada())
    <span class="badge text-bg-warning">Atrasada</span>
@endif