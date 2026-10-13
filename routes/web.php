<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MarcaController;
use App\Http\Controllers\BairroController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\VeiculoController;
use App\Http\Controllers\FuncionarioController;
use App\Http\Controllers\MapaRotasController;
use App\Http\Controllers\RotaController;
use App\Http\Controllers\RotaVersaoController;
use App\Http\Controllers\SenhaTemporariaController;
use App\Http\Controllers\ProgramacaoColetaController;

Route::redirect('/', '/painel');

Route::middleware('auth')->group(function () {

    Route::view('/painel', 'painel')->name('painel');

    Route::get('/senha/temporaria', [SenhaTemporariaController::class, 'edit'])->name('senha.temporaria.edit');
    Route::put('/senha/temporaria', [SenhaTemporariaController::class, 'update'])->name('senha.temporaria.update');

    Route::middleware('perfil:TECNICO,GESTOR')->group(function () {
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
        Route::post('/programacoes/{programacao}/encerrar', [ProgramacaoColetaController::class, 'encerrar'])->name('programacoes.encerrar');});

    Route::middleware('perfil:TECNICO')->group(function () {
        Route::resource('usuarios', UsuarioController::class)->except('show', 'destroy');

        Route::post('/usuarios/{usuario}/redefinir-senha', [UsuarioController::class, 'redefinirSenha'])->name('usuarios.redefinir-senha');
    });

});

require __DIR__.'/auth.php';