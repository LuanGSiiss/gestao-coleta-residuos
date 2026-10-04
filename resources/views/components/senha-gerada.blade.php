@php $senha_gerada = session('senha_gerada'); @endphp

@if ($senha_gerada)
    <div class="alert alert-warning" role="alert" x-data="{ copiada: false }">
        <h2 class="h6">Senha temporária de {{ $senha_gerada['nome'] }}</h2>
        <p class="mb-2">
            Entregue ao usuário, junto com o e-mail <strong>{{ $senha_gerada['email'] }}</strong>. A troca será obrigatória no primeiro acesso.
        </p>

        <div class="d-flex align-items-center gap-2 flex-wrap">
            <code class="fs-5 px-2 py-1 bg-body border rounded user-select-all">{{ $senha_gerada['senha'] }}</code>

            <button type="button" class="btn btn-sm btn-outline-dark" data-senha="{{ $senha_gerada['senha'] }}"
                    x-on:click="navigator.clipboard.writeText($el.dataset.senha); copiada = true">
                <span x-show="!copiada">Copiar</span>
                <span x-show="copiada" x-cloak>Copiada</span>
            </button>
        </div>

        <p class="small mb-0 mt-2">
            Esta é a única vez que a senha é exibida. Se for perdida, redefina novamente.
        </p>
    </div>
@endif