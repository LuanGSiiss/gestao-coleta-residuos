@props(['ocorrencias'])

<ul class="list-group list-group-flush">
    @forelse ($ocorrencias as $ocorrencia)
        <li class="list-group-item">
            <div class="d-flex flex-wrap justify-content-between column-gap-3">
                <strong>{{ $ocorrencia->titulo }}</strong>
                <span class="small text-body-secondary">{{ $ocorrencia->data_hora->format('d/m/Y H:i') }}</span>
            </div>

            <div class="texto-preservado">{{ $ocorrencia->descricao }}</div>

            @if ($ocorrencia->endereco)
                <div class="small mt-1">Endereço: {{ $ocorrencia->endereco }}</div>
            @endif

            <div class="small text-body-secondary mt-1">
                Registrada por {{ $ocorrencia->usuario->nome }} em {{ $ocorrencia->registrada_em->format('d/m/Y H:i') }}
            </div>
        </li>
    @empty
        <li class="list-group-item small text-body-secondary">Nenhuma ocorrência registrada.</li>
    @endforelse
</ul>