@use('App\Support\Voltar')
@extends('layouts.app')

@php 
    $editando = $programacao->exists;
    $voltar = Voltar::url($editando ? route('programacoes.show', $programacao) : route('programacoes.index'));
@endphp

@section('titulo', $editando ? "Editar programação {$programacao->id}" : 'Nova programação')

@section('acoes')
    <div class="d-flex gap-2">
        <a href="{{ $voltar }}" class="btn btn-outline-secondary">Cancelar</a>
        <button type="submit" form="form-programacao" class="btn btn-primary">{{ $editando ? 'Salvar alterações' : 'Cadastrar' }}</button>
    </div>
@endsection

@section('conteudo')
    <form id="form-programacao" method="POST" action="{{ $editando ? route('programacoes.update', $programacao) : route('programacoes.store') }}">
        @csrf
        @if ($editando) @method('PUT') @endif
        <input type="hidden" name="{{ Voltar::CAMPO }}" value="{{ $voltar }}">

        @if ($editando)
            <div class="alert alert-info">
                @if ($futuras === 0)
                    Esta programação não tem execuções futuras ainda não iniciadas: a alteração fica só na programação e vale para as execuções de uma prorrogação.
                @else
                    A alteração vale para {{ $futuras === 1 ? 'a execução futura ainda não iniciada' : "as {$futuras} execuções futuras ainda não iniciadas" }}.
                    Execuções atrasadas, em andamento, concluídas e canceladas não mudam.
                @endif
            </div>
        @elseif (empty($rotas) || empty($motoristas) || empty($veiculos) || $coletores->isEmpty())
            <div class="alert alert-warning">
                Para cadastrar uma programação, é preciso ter ao menos uma rota ativa com trajeto e um
                motorista, um coletor e um veículo ativos.
            </div>
        @endif

        <div class="card mb-3">
            <div class="card-body">
                <div class="row">
                    <div class="col-sm-4 col-md-3">
                        <x-form.leitura label="Código" :valor="$programacao->id" vazio="Gerado ao salvar" />
                    </div>
                </div>

                <div class="row">
                    <div class="{{ $editando ? 'col-12' : 'col-md-8' }}">
                        <x-form.input name="descricao" label="Descrição" :value="$programacao->descricao" required maxlength="120" autofocus placeholder="Coleta convencional — Centro, manhã" />
                    </div>

                    @unless ($editando)
                        <div class="col-md-4">
                            <x-form.select name="tipo_coleta" label="Tipo de coleta" :opcoes="$tipos" required />
                        </div>
                    @endunless
                </div>

                @if ($editando)
                    <div class="row">
                        <div class="col-sm-4">
                            <x-form.leitura label="Tipo de coleta" :valor="$programacao->tipo_coleta->rotulo()" />
                        </div>
                        <div class="col-sm-8">
                            <x-form.leitura label="Rota">
                                <span class="amostra-cor" style="background-color: {{ $programacao->rota->cor }}"></span>
                                <span class="ms-1">{{ $programacao->rota->identificacao }} — {{ $programacao->rota->nome }}</span>
                            </x-form.leitura>
                        </div>
                    </div>
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
                        <x-form.coletores :coletores="$coletores" :selecionados="$coletoresSelecionados" />
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header small fw-semibold">Cronograma</div>
            <div class="card-body">
                @if ($editando)
                    <div class="row">
                        <div class="col-md-4">
                            <x-form.leitura label="Data de início" :valor="$programacao->data_inicio->format('d/m/Y')" />
                        </div>
                        <div class="col-md-4">
                            <x-form.leitura label="Data de término" :valor="$programacao->data_fim->format('d/m/Y')" />
                        </div>
                    </div>

                    <x-form.leitura label="Dias da semana">
                        <x-dias-semana :dias="$programacao->dias()" />
                    </x-form.leitura>

                    <p class="small text-body-secondary">
                        Tipo, rota, período e dias não mudam depois do cadastro. Para estender o período, use
                        <a href="{{ route('programacoes.prorrogacao', $programacao) }}">Prorrogar</a>
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
                                <label class="btn btn-outline-primary" for="dia-{{ $dia->value }}" title="{{ $dia->rotulo() }}">
                                    {{ $dia->abreviado() }}
                                </label>
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