<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RotaController;
use App\Http\Controllers\MarcaController;
use App\Http\Controllers\BairroController;
use App\Http\Controllers\PainelController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\VeiculoController;
use App\Http\Controllers\ExecucaoController;
use App\Http\Controllers\MapaRotasController;
use App\Http\Controllers\OcorrenciaController;
use App\Http\Controllers\RotaVersaoController;
use App\Http\Controllers\FuncionarioController;
use App\Http\Controllers\IndicadoresController;
use App\Http\Controllers\TelaInicialController;
use App\Http\Controllers\MinhasColetasController;
use App\Http\Controllers\SenhaTemporariaController;
use App\Http\Controllers\ProgramacaoColetaController;

Route::middleware('auth')->group(function () {

    Route::get('/', TelaInicialController::class)->name('tela-inicial');

    Route::get('/senha/temporaria', [SenhaTemporariaController::class, 'edit'])->name('senha.temporaria.edit');
    Route::put('/senha/temporaria', [SenhaTemporariaController::class, 'update'])->name('senha.temporaria.update');

    Route::middleware('perfil:TECNICO,GESTOR')->group(function () {
        Route::get('/painel', PainelController::class)->name('painel');
        Route::get('/indicadores', IndicadoresController::class)->name('indicadores');

        Route::resource('marcas',  MarcaController::class)->except('show');
        Route::resource('bairros', BairroController::class)->except('show');
        Route::resource('funcionarios', FuncionarioController::class)->except('show');
        Route::resource('veiculos', VeiculoController::class)->except('show');
        
        Route::get('/rotas/mapa', [MapaRotasController::class, 'index'])->name('rotas.mapa');
        Route::get('/rotas/{rota}/versoes/{numero?}', [RotaVersaoController::class, 'index'])->whereNumber('numero')->name('rotas.versoes');
        Route::resource('rotas', RotaController::class)->except('destroy');
        
        Route::resource('programacoes', ProgramacaoColetaController::class)->parameters(['programacoes' => 'programacao'])->except('destroy');
        Route::get('/programacoes/{programacao}/prorrogar', [ProgramacaoColetaController::class, 'prorrogacao'])->name('programacoes.prorrogacao');
        Route::put('/programacoes/{programacao}/prorrogar', [ProgramacaoColetaController::class, 'prorrogar'])->name('programacoes.prorrogar');
        Route::post('/programacoes/{programacao}/encerrar', [ProgramacaoColetaController::class, 'encerrar'])->name('programacoes.encerrar');
    
        Route::resource('execucoes', ExecucaoController::class)->parameters(['execucoes' => 'execucao'])->only(['index', 'show', 'edit', 'update']);
        Route::get('/execucoes/{execucao}/cancelar', [ExecucaoController::class, 'cancelamento'])->name('execucoes.cancelamento');
        Route::put('/execucoes/{execucao}/cancelar', [ExecucaoController::class, 'cancelar'])->name('execucoes.cancelar');
        Route::get('/execucoes/{execucao}/ocorrencias/nova', [OcorrenciaController::class, 'create'])->name('execucoes.ocorrencias.create');
        Route::post('/execucoes/{execucao}/ocorrencias', [OcorrenciaController::class, 'store'])->name('execucoes.ocorrencias.store');
    });

    Route::middleware('perfil:MOTORISTA')->prefix('minhas-coletas')->name('coletas.')->controller(MinhasColetasController::class)->group(function () {
            Route::get('/', 'index')->name('index');

            Route::middleware('can:acessarComoMotorista,execucao')->group(function () {
                Route::get('/{execucao}', 'show')->name('show');
                Route::get('/{execucao}/iniciar', 'inicio')->name('inicio');
                Route::put('/{execucao}/iniciar', 'iniciar')->name('iniciar');
                Route::get('/{execucao}/concluir', 'conclusao')->name('conclusao');
                Route::put('/{execucao}/concluir', 'concluir')->name('concluir');
                Route::get('/{execucao}/ocorrencias/nova', 'novaOcorrencia')->name('ocorrencias.create');
                Route::post('/{execucao}/ocorrencias', 'registrarOcorrencia')->name('ocorrencias.store');
            });
    });

    Route::middleware('perfil:TECNICO')->group(function () {
        Route::resource('usuarios', UsuarioController::class)->except('show', 'destroy');

        Route::post('/usuarios/{usuario}/redefinir-senha', [UsuarioController::class, 'redefinirSenha'])->name('usuarios.redefinir-senha');
    });

});

require __DIR__.'/auth.php';