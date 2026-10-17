@props([
    'coletores',              // os oferecidos: OpcoesAlocacao::coletores()
    'selecionados' => [],     // ids marcados
])

<div class="mb-3"
     x-data="{
         filtro: '',
         normalizar: (texto) => texto.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase(),
     }">
    <label for="filtro-coletores" class="form-label obrigatorio">Coletores</label>

    @if ($coletores->isNotEmpty())
        <input type="search" id="filtro-coletores" class="form-control form-control-sm mb-2" placeholder="Filtrar coletores…" x-model="filtro">

        <div class="lista-opcoes border rounded px-2 py-1 {{ $errors->has('coletores') || $errors->has('coletores.*') ? 'border-danger' : '' }}">
            @foreach ($coletores as $coletor)
                <div class="form-check" x-show="normalizar(@js($coletor->nome)).includes(normalizar(filtro))">
                    <input class="form-check-input" type="checkbox" name="coletores[]" value="{{ $coletor->id }}" id="coletor-{{ $coletor->id }}" @checked(in_array($coletor->id, $selecionados, true))>
                    <label class="form-check-label" for="coletor-{{ $coletor->id }}">
                        {{ $coletor->nome }}
                        @unless ($coletor->ativo)
                            <span class="text-body-secondary">(inativo)</span>
                        @endunless
                    </label>
                </div>
            @endforeach
        </div>
    @else
        <p class="small text-body-secondary mb-0">Nenhum coletor ativo cadastrado.</p>
    @endif

    @error('coletores')
        <div class="text-danger small mt-1">{{ $message }}</div>
    @enderror
    @error('coletores.*')
        <div class="text-danger small mt-1">{{ $message }}</div>
    @enderror
</div>