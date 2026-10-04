<nav class="navbar navbar-expand-lg navbar-dark navbar-marca">
    <div class="container">
        <a class="navbar-brand fw-semibold" href="{{ route('painel') }}">Gestão da Coleta de Resíduos</a>

        <button class="navbar-toggler" type="button"
                data-bs-toggle="collapse" data-bs-target="#menu-principal"
                aria-controls="menu-principal" aria-expanded="false"
                aria-label="Alternar navegação">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="menu-principal">
            <ul class="navbar-nav me-auto">

                @if (auth()->user()->perfil->gerencia())
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('painel') ? 'active' : '' }}" href="{{ route('painel') }}">Painel</a>
                    </li>

                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle {{ request()->routeIs('bairros.*', 'funcionarios.*', 'marcas.*', 'veiculos.*') ? 'active' : '' }}"
                            href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Cadastros
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="{{ route('bairros.index') }}">Bairros</a></li>
                            <li><a class="dropdown-item" href="{{ route('funcionarios.index') }}">Funcionários</a></li>
                            <li><a class="dropdown-item" href="{{ route('marcas.index') }}">Marcas de Veículo</a></li>
                            <li><a class="dropdown-item" href="{{ route('veiculos.index') }}">Veículos</a></li>
                        </ul>
                    </li>
                @endif

                @if (auth()->user()->perfil->administra())
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('usuarios.*') ? 'active' : '' }}" href="{{ route('usuarios.index') }}">Usuários</a>
                    </li>
                @endif

            </ul>

            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">{{ auth()->user()->nome }}</a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <span class="dropdown-item-text small text-secondary">{{ auth()->user()->perfil->rotulo() }}</span>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item">Sair</button>
                            </form>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>