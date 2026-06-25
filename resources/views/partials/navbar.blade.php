{{-- Barra di navigazione con menu condizionato dal ruolo (Spatie @role / @can) --}}
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand fw-semibold" href="{{ route('dashboard') }}">
            <i class="bi bi-calendar2-check me-1"></i> Coordinamento Esami
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain"
            aria-controls="navbarMain" aria-expanded="false" aria-label="Apri il menu">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarMain">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"
                        href="{{ route('dashboard') }}">Dashboard</a>
                </li>

                {{-- Voci riservate all'amministratore --}}
                @role('amministratore')
                    @if (Route::has('struttura.index'))
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('struttura.*') ? 'active' : '' }}"
                                href="{{ route('struttura.index') }}">Struttura didattica</a>
                        </li>
                    @endif
                    @if (Route::has('import.index'))
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('import.*') ? 'active' : '' }}"
                                href="{{ route('import.index') }}">Importa CSV</a>
                        </li>
                    @endif
                    @if (Route::has('configurazione.edit'))
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('configurazione.*') ? 'active' : '' }}"
                                href="{{ route('configurazione.edit') }}">Configurazione</a>
                        </li>
                    @endif
                @endrole

                {{-- Appelli: i docenti gestiscono i propri, l'admin li vede tutti --}}
                @if (Route::has('appelli.index'))
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('appelli.*') ? 'active' : '' }}"
                            href="{{ route('appelli.index') }}">@role('docente') I miei appelli @else Appelli @endrole</a>
                    </li>
                @endif

                {{-- Calendario: visibile a tutti gli utenti autenticati --}}
                @if (Route::has('calendario.index'))
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('calendario.*') ? 'active' : '' }}"
                            href="{{ route('calendario.index') }}">Calendario</a>
                    </li>
                @endif
            </ul>

            {{-- Menu utente --}}
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="userMenu" role="button"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person-circle me-1"></i>{{ Auth::user()->name }}
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userMenu">
                        <li>
                            <span class="dropdown-item-text small text-muted">
                                {{ Auth::user()->getRoleNames()->map(fn ($r) => ucfirst($r))->implode(', ') }}
                            </span>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="{{ route('profile.show') }}">Profilo</a></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item">Esci</button>
                            </form>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>
