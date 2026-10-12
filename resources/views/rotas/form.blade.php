@use('App\Support\Retorno')
@extends('layouts.app')

@php $editando = $rota->exists; @endphp

@section('titulo', $editando ? "Editar rota {$rota->identificacao}" : 'Nova rota')

@push('scripts')
    @vite('resources/js/mapa/index.js')
@endpush

@section('acoes')
    <div class="d-flex gap-2">
        @if ($editando)
            <a href="{{ Retorno::link('rotas.versoes', $rota) }}" class="btn btn-outline-secondary">Versões</a>
        @endif
        <a href="{{ Retorno::destino($editando ? route('rotas.show', $rota) : route('rotas.index')) }}" class="btn btn-outline-secondary">Cancelar</a>
        <button type="submit" form="form-rota" class="btn btn-primary">{{ $editando ? 'Salvar alterações' : 'Cadastrar' }}</button>
    </div>
@endsection

@section('conteudo')
    <form id="form-rota" method="POST" action="{{ $editando ? route('rotas.update', $rota) : route('rotas.store') }}">
        @csrf
        @if ($editando) 
            @method('PUT') 
        @endif
        <input type="hidden" name="voltar" value="{{ Retorno::atual() }}">

        <div class="card mb-3">
            <div class="card-body">
                <x-form.codigo :valor="$rota->id" />

                <div class="row">
                    <div class="col-md-3">
                        <x-form.input name="identificacao" label="Identificação" :value="$rota->identificacao" required maxlength="20" placeholder="N01" autofocus/>
                    </div>
                    <div class="col-md-6">
                        <x-form.input name="nome" label="Nome" :value="$rota->nome" required maxlength="120" />
                    </div>
                    <div class="col-md-3">
                        <x-form.input name="cor" label="Cor no mapa" type="color" :value="$cor" required class="form-control-color w-100" />
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-7">
                        <x-form.textarea name="descricao" label="Descrição" :value="$rota->descricao" required :linhas="4" maxlength="2000" />
                    </div>

                    <div class="col-md-5">
                        <div class="mb-3"
                             x-data="{
                                 filtro: '',
                                 normalizar: (texto) => texto.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase(),
                             }">
                            <label for="filtro-bairros" class="form-label obrigatorio">Bairros de abrangência</label>

                            @if ($bairros->isEmpty())
                                <div class="alert alert-warning small mb-0">
                                    Nenhum bairro ativo cadastrado.
                                    <a href="{{ route('bairros.create') }}">Cadastre um bairro</a> antes de criar a rota.
                                </div>
                            @else
                                <input type="search" id="filtro-bairros" class="form-control form-control-sm mb-2" placeholder="Filtrar bairros…" x-model="filtro">

                                <div class="lista-bairros border rounded px-2 py-1 {{ $errors->has('bairros') || $errors->has('bairros.*') ? 'border-danger' : '' }}">
                                    @foreach ($bairros as $id => $nome)
                                        <div class="form-check" x-show="normalizar(@js($nome)).includes(normalizar(filtro))">
                                            <input class="form-check-input" type="checkbox" name="bairros[]"
                                                   value="{{ $id }}" id="bairro-{{ $id }}"
                                                   @checked(in_array($id, $selecionados, true))>
                                            <label class="form-check-label" for="bairro-{{ $id }}">{{ $nome }}</label>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            <div class="form-text">
                                Categoriza a rota: um bairro pode ser atendido em parte, e por mais de uma rota.
                            </div>

                            @error('bairros')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                            @error('bairros.*')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <x-form.checkbox name="ativo" label="Rota ativa" :checked="$editando ? $rota->ativo : true" />
            </div>
        </div>

        <div class="card">
            @error('trajeto')
                <div class="alert alert-danger rounded-0 mb-0">{{ $message }}</div>
            @enderror

            {{-- Os data- apontam, pelo id, para os elementos que o JavaScript usa --}}
            <div id="mapa-rota"
                 class="mapa-editor"
                 data-mapa="editor-rota"
                 data-dados="mapa-rota-dados"
                 data-entrada="trajeto"
                 data-campo-cor="cor"
                 data-dica="mapa-dica"
                 data-extensao="mapa-extensao"
                 data-contagem="mapa-trechos"></div>

            <div class="card-footer small">
                <div class="d-flex justify-content-between flex-wrap gap-2">
                    <span id="mapa-dica" class="text-body-secondary" aria-live="polite"></span>

                    <span class="text-nowrap">
                        Extensão aproximada: <strong id="mapa-extensao">0,00 km</strong>
                        · <span id="mapa-trechos">0</span> trecho(s)
                        · <a href="#atalhos-mapa" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="atalhos-mapa">Atalhos</a>
                    </span>
                </div>

                <div class="collapse" id="atalhos-mapa">
                    <dl class="row mb-0 mt-3">
                        <dt class="col-sm-3">Desenhar trecho</dt>
                        <dd class="col-sm-9">
                            Clique em cada vértice e no último para concluir. Os botões que aparecem ao
                            lado da ferramenta desfazem o último vértice ou cancelam o trecho.
                        </dd>

                        <dt class="col-sm-3">Esc</dt>
                        <dd class="col-sm-9">Sai da ferramenta ativa. Durante o desenho, descarta o trecho em andamento.</dd>

                        <dt class="col-sm-3">Alt, segurado</dt>
                        <dd class="col-sm-9">Desliga o alinhamento automático enquanto desenha ou arrasta.</dd>

                        <dt class="col-sm-3">Editar trechos</dt>
                        <dd class="col-sm-9">Arraste os vértices. arraste os pontos intermediários para criar vértices. O botão direito sobre um vértice o remove.</dd>

                        <dt class="col-sm-3">Recortar trecho</dt>
                        <dd class="col-sm-9">Desenhe um polígono sobre a parte do trecho a retirar.</dd>

                        <dt class="col-sm-3">Remover trecho</dt>
                        <dd class="col-sm-9">Clique sobre o trecho.</dd>

                        <dt class="col-sm-3">Linhas cinza tracejadas</dt>
                        <dd class="col-sm-9 mb-0">
                            Outras rotas para referência. Não podem ser alteradas aqui, mas os vértices novos se alinham a elas.
                        </dd>
                    </dl>
                </div>
            </div>
        </div>

        {{-- Preenchido pelo JavaScript no envio, com o GeoJSON dos trechos --}}
        <input type="hidden" name="trajeto" id="trajeto">
    </form>

    <script type="application/json" id="mapa-rota-dados">@json($mapa)</script>
@endsection