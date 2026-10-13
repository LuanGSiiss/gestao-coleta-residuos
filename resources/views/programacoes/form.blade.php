@use('App\Support\Retorno')
@extends('layouts.app')

@php $editando = $programacao->exists; @endphp

@section('titulo', $editando ? "Editar programação {$programacao->id}" : 'Nova programação')

@section('acoes')
    <div class="d-flex gap-2">
        <a href="{{ Retorno::destino($editando ? route('programacoes.show', $programacao) : route('programacoes.index')) }}" class="btn btn-outline-secondary">Cancelar</a>
        <button type="submit" form="form-programacao" class="btn btn-primary">{{ $editando ? 'Salvar alterações' : 'Cadastrar' }}</button>
    </div>
@endsection

@section('conteudo')
    <form id="form-programacao" method="POST"
          action="{{ $editando ? route('programacoes.update', $programacao) : route('programacoes.store') }}">
        @csrf
        @if ($editando) 
            @method('PUT') 
        @endif
        <input type="hidden" name="voltar" value="{{ Retorno::atual() }}">

        @if ($editando)
            <div class="alert alert-info">
                @if ($futuras === 0)
                    Esta programação não tem execuções futuras ainda não iniciadas: a alteração fica só na programação e vale para as execuções de uma prorrogação.
                @else
                    A alteração vale para
                    {{ $futuras === 1 ? 'a execução futura ainda não iniciada' : "as {$futuras} execuções futuras ainda não iniciadas" }}.
                    Execuções atrasadas, em andamento, concluídas e canceladas não mudam.
                @endif
            </div>
        @elseif (empty($rotas) || empty($motoristas) || empty($veiculos) || $coletores->isEmpty())
            <div class="alert alert-warning">
                Para cadastrar uma programação, é preciso ter ao menos uma rota ativa com trajeto e um motorista, um coletor e um veículo ativos. Confira os cadastros de
                <a href="{{ route('rotas.index') }}">rotas</a>,
                <a href="{{ route('funcionarios.index') }}">funcionários</a> e
                <a href="{{ route('veiculos.index') }}">veículos</a>.
            </div>
        @endif

        <div class="card mb-3">
            <div class="card-body">
                <x-form.codigo :valor="$programacao->id" />

                <div class="row">
                    <div class="{{ $editando ? 'col-12' : 'col-md-8' }}">
                        <x-form.input name="descricao" label="Descrição" :value="$programacao->descricao"
                                      required maxlength="120" autofocus
                                      placeholder="Coleta convencional — Centro, manhã" />
                    </div>

                    @unless ($editando)
                        <div class="col-md-4">
                            <x-form.select name="tipo_coleta" label="Tipo de coleta" :opcoes="$tipos" required />
                        </div>
                    @endunless
                </div>

                @if ($editando)
                    <dl class="dados row mb-3">
                        <div class="col-sm-4">
                            <dt>Tipo de coleta</dt>
                            <dd>{{ $programacao->tipo_coleta->rotulo() }}</dd>
                        </div>
                        <div class="col-sm-8">
                            <dt>Rota</dt>
                            <dd>
                                <span class="amostra-cor" style="background-color: {{ $programacao->rota->cor }}"></span>
                                <span class="ms-1">{{ $programacao->rota->identificacao }} — {{ $programacao->rota->nome }}</span>
                            </dd>
                        </div>
                    </dl>
                @else
                    <x-form.select name="rota_id" label="Rota" :opcoes="$rotas" required />
                @endif

                <x-form.textarea name="orientacoes" label="Orientações" :value="$programacao->orientacoes" :linhas="3" maxlength="2000" />
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header small fw-semibold">Equipe e veículo</div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <x-form.select name="motorista_id" label="Motorista" :opcoes="$motoristas" :value="$programacao->motorista_id" required />

                        <x-form.select name="veiculo_id" label="Veículo" :opcoes="$veiculos" :value="$programacao->veiculo_id" required />
                    </div>

                    <div class="col-md-6">
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
                                            <input class="form-check-input" type="checkbox" name="coletores[]"
                                                   value="{{ $coletor->id }}" id="coletor-{{ $coletor->id }}"
                                                   @checked(in_array($coletor->id, $coletoresSelecionados, true))>
                                            <label class="form-check-label" for="coletor-{{ $coletor->id }}">
                                                {{ $coletor->nome }}
                                                @unless ($coletor->ativo)
                                                    <span class="text-body-secondary">(inativo)</span>
                                                @endunless
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            @error('coletores')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                            @error('coletores.*')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header small fw-semibold">Cronograma</div>
            <div class="card-body">
                @if ($editando)
                    <dl class="dados row mb-2">
                        <div class="col-sm-4">
                            <dt>Período</dt>
                            <dd>{{ $programacao->periodo }}</dd>
                        </div>
                        <div class="col-sm-8">
                            <dt>Dias da semana</dt>
                            <dd>{{ $programacao->dias_abreviados }}</dd>
                        </div>
                    </dl>

                    <p class="small text-body-secondary">
                        Tipo, rota, período e dias não mudam depois do cadastro. Para estender o período, use <a href="{{ Retorno::link('programacoes.prorrogacao', $programacao) }}">Prorrogar</a>.
                        para mudar os demais, encerre esta programação e cadastre outra.
                    </p>
                @else
                    <div class="row">
                        <div class="col-md-4">
                            <x-form.input name="data_inicio" label="Data de início" type="date" required :min="today()->toDateString()" />
                        </div>
                        <div class="col-md-4">
                            <x-form.input name="data_fim" label="Data de término" type="date" required ajuda="No máximo 12 meses depois do início." />
                        </div>
                    </div>

                    <div class="mb-3">
                        <span id="rotulo-dias" class="form-label d-block obrigatorio">Dias da semana</span>

                        <div class="btn-group flex-wrap" role="group" aria-labelledby="rotulo-dias">
                            @foreach ($dias as $dia)
                                <input type="checkbox" class="btn-check" name="dias_semana[]" value="{{ $dia->value }}"
                                       id="dia-{{ $dia->value }}" autocomplete="off"
                                       @checked(in_array($dia->value, $diasSelecionados, true))>
                                <label class="btn btn-outline-primary" for="dia-{{ $dia->value }}" title="{{ $dia->rotulo() }}">{{ $dia->abreviado() }}</label>
                            @endforeach
                        </div>

                        @error('dias_semana')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                        @error('dias_semana.*')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                @endif

                <div class="row">
                    <div class="col-md-4">
                        <x-form.input name="hora_inicio" label="Hora de início" type="time" :value="$programacao->hora_inicio" required />
                    </div>
                    <div class="col-md-4">
                        <x-form.input name="hora_fim" label="Hora de término" type="time" :value="$programacao->hora_fim" required />
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection