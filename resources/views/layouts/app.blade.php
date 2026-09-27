<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@yield('titulo', 'Gestão da Coleta de Resíduos') — {{ config('app.name') }}</title>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        @include('layouts.navegacao')
        
        <main class="container py-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
            <div>
                <h1 class="h4 mb-0">@yield('titulo')</h1>
                @hasSection('subtitulo')
                    <p class="text-secondary mb-0 small">@yield('subtitulo')</p>
                @endif
            </div>

            @yield('acoes')
        </div>

        <x-alertas />

        @yield('conteudo')
    </main>
    </body>
</html>
