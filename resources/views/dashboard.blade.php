@extends('layouts.master')

@section('title', 'Dashboard')
@section('heading', 'Dashboard')

@section('content')

    @if ($ruolo === 'amministratore')

        {{-- ============ Dashboard amministratore ============ --}}
        <div class="row g-3 mb-4">
            <div class="col-6 col-lg-3">
                <div class="card text-bg-primary h-100">
                    <div class="card-body">
                        <div class="display-6 fw-bold">{{ $stats['corsi'] }}</div>
                        <div class="small text-uppercase">Corsi di studio</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card text-bg-success h-100">
                    <div class="card-body">
                        <div class="display-6 fw-bold">{{ $stats['insegnamenti'] }}</div>
                        <div class="small text-uppercase">Insegnamenti</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card text-bg-info h-100">
                    <div class="card-body">
                        <div class="display-6 fw-bold">{{ $stats['sessioni'] }}</div>
                        <div class="small text-uppercase">Sessioni</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card text-bg-warning h-100">
                    <div class="card-body">
                        <div class="display-6 fw-bold">{{ $stats['appelli'] }}</div>
                        <div class="small text-uppercase">Appelli</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header fw-semibold">Prossimi appelli</div>
            <div class="card-body p-0">
                @if ($prossimiAppelli->isEmpty())
                    <p class="text-muted m-3">Nessun appello in programma.</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Orario</th>
                                    <th>Insegnamento</th>
                                    <th>Docente</th>
                                    <th>Aula</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($prossimiAppelli as $appello)
                                    <tr>
                                        <td>{{ $appello->data->format('d/m/Y') }}</td>
                                        <td>{{ \Illuminate\Support\Str::substr($appello->ora_inizio, 0, 5) }}&ndash;{{ \Illuminate\Support\Str::substr($appello->ora_fine, 0, 5) }}</td>
                                        <td>{{ $appello->insegnamento->nome }}</td>
                                        <td>{{ $appello->docente->name }}</td>
                                        <td>{{ $appello->aula ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

    @else

        {{-- ============ Dashboard docente ============ --}}
        <div class="alert alert-info">
            Benvenuto, <strong>{{ Auth::user()->name }}</strong>. Da qui puoi gestire i tuoi appelli d'esame.
        </div>

        <div class="row g-4">
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header fw-semibold">I miei insegnamenti</div>
                    <ul class="list-group list-group-flush">
                        @forelse ($insegnamenti as $insegnamento)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span>
                                    {{ $insegnamento->nome }}
                                    <small class="text-muted">({{ $insegnamento->corsoStudio->nome }})</small>
                                </span>
                                <span class="badge text-bg-secondary">{{ $insegnamento->anno_frequenza }}° anno</span>
                            </li>
                        @empty
                            <li class="list-group-item text-muted">Nessun insegnamento associato.</li>
                        @endforelse
                    </ul>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header fw-semibold">I miei prossimi appelli</div>
                    <ul class="list-group list-group-flush">
                        @forelse ($mieiAppelli as $appello)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span>
                                    {{ $appello->insegnamento->nome }}
                                    <small class="text-muted d-block">{{ $appello->aula ?? 'Aula da definire' }}</small>
                                </span>
                                <span class="badge text-bg-primary">{{ $appello->data->format('d/m/Y') }}</span>
                            </li>
                        @empty
                            <li class="list-group-item text-muted">Nessun appello in programma.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>

    @endif

@endsection
