<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MarcaController;
use App\Http\Controllers\BairroController;

Route::redirect('/', '/painel');

Route::middleware('auth')->group(function () {

    Route::view('/painel', 'painel')->name('painel');

    Route::middleware('perfil:TECNICO,GESTOR')->group(function () {
        Route::resource('marcas',  MarcaController::class)->except('show');
        Route::resource('bairros', BairroController::class)->except('show');
    });

});

require __DIR__.'/auth.php';