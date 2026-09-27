<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titulo', 'Acesso') — {{ config('app.name') }}</title>
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="d-flex align-items-center min-vh-100">

    <main class="container" style="max-width: 26rem;">
        <div class="text-center mb-4">
            <h1 class="h4 mb-1">Coleta RSU</h1>
            <p class="text-secondary small mb-0">Gestão logística da coleta de resíduos</p>
        </div>

        <div class="card shadow-sm">
            <div class="card-body p-4">
                @yield('conteudo')
            </div>
        </div>
    </main>

</body>
</html>