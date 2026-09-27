<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Como a paginação do Laravel usa views com Tailwind por padrão, carrega os estilos do Bootstrap.
        Paginator::useBootstrapFive();

        // 2. Impede atribuição em massa de campos que não estão em $fillable e o acesso a relacionamentos não carregados.
        Model::shouldBeStrict($this->app->isLocal());
    }
}
