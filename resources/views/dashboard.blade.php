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

        {{-- Monitoraggio scadenze: insegnamenti senza appello con finestra in scadenza o chiusa --}}
        @if ($segnalazioni->isNotEmpty())
            <div class="card border-warning mb-4">
                <div class="card-header fw-semibold bg-warning-subtle text-warning-emphasis">
                    <i class="bi bi-exclamation-triangle"></i> Monitoraggio scadenze — appelli mancanti
                </div>
                <div class="card-body">
                    <p class="small text-muted mb-3">
                        Insegnamenti ancora privi di appello in sessioni la cui finestra di inserimento è in scadenza o già chiusa.
                    </p>
                    @foreach ($segnalazioni as $seg)
                        @php
                            $badge = $seg['stato'] === 'chiusa'
                                ? ['danger', 'finestra chiusa']
                                : ['warning', 'finestra in scadenza'];
                            $scadenza = $seg['sessione']->periodiInserimento->max('data_fine');
                        @endphp
                        <div class="mb-3">
                            <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                <strong>{{ $seg['sessione']->nome }}</strong>
                                <span class="badge text-bg-{{ $badge[0] }}">{{ $badge[1] }}</span>
                                @if ($scadenza)
                                    <span class="text-muted small">inserimento entro il {{ $scadenza->format('d/m/Y') }}</span>
                                @endif
                            </div>
                            <ul class="mb-0">
                                @foreach ($seg['insegnamenti'] as $ins)
                                    <li>
                                        {{ $ins->nome }}
                                        <span class="text-muted">({{ $ins->corsoStudio->nome }}, {{ $ins->anno_frequenza }}° anno)</span>
                                        @if ($ins->docenti->isNotEmpty())
                                            — <span class="text-muted small">{{ $ins->docenti->pluck('name')->join(', ') }}</span>
                                        @else
                                            — <span class="badge text-bg-secondary">nessun docente associato</span>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

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

        {{-- Promemoria: insegnamenti del docente ancora senza appello nelle sessioni attive --}}
        @if ($daCompletare->isNotEmpty())
            <div class="card border-warning mb-4">
                <div class="card-header fw-semibold bg-warning-subtle text-warning-emphasis">
                    <i class="bi bi-exclamation-triangle"></i> Insegnamenti da pianificare
                </div>
                <div class="card-body">
                    <p class="small text-muted mb-3">
                        Questi tuoi insegnamenti non hanno ancora un appello nella sessione indicata.
                    </p>
                    @foreach ($daCompletare as $seg)
                        @php
                            $mappa = [
                                'aperta' => ['secondary', 'inserimento aperto'],
                                'in_scadenza' => ['warning', 'in scadenza'],
                                'chiusa' => ['danger', 'finestra chiusa'],
                            ];
                            $badge = $mappa[$seg['stato']];
                            $scadenza = $seg['sessione']->periodiInserimento->max('data_fine');
                        @endphp
                        <div class="mb-3">
                            <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                <strong>{{ $seg['sessione']->nome }}</strong>
                                <span class="badge text-bg-{{ $badge[0] }}">{{ $badge[1] }}</span>
                                @if ($scadenza)
                                    <span class="text-muted small">inserimento entro il {{ $scadenza->format('d/m/Y') }}</span>
                                @endif
                            </div>
                            <ul class="mb-0">
                                @foreach ($seg['insegnamenti'] as $ins)
                                    <li>
                                        {{ $ins->nome }}
                                        <span class="text-muted">({{ $ins->corsoStudio->nome }}, {{ $ins->anno_frequenza }}° anno)</span>
                                        @if ($seg['stato'] !== 'chiusa')
                                            <a href="{{ route('appelli.create', ['insegnamento' => $ins->id, 'sessione' => $seg['sessione']->id]) }}" class="ms-1">crea appello</a>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

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
