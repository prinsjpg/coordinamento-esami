@extends('layouts.master')

@section('title', 'Calendario')
@section('heading', 'Calendario degli appelli')

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">Calendario</li>
@endsection

@section('content')

    {{-- Selettore della sessione --}}
    <form method="GET" action="{{ route('calendario.index') }}" class="row g-2 align-items-end mb-4" style="max-width: 32rem;">
        <div class="col">
            <label for="sessione" class="form-label">Sessione</label>
            <select name="sessione" id="sessione" class="form-select" onchange="this.form.submit()">
                @forelse ($sessioni as $s)
                    <option value="{{ $s->id }}" @selected($sessioneSelezionata && $sessioneSelezionata->id === $s->id)>
                        {{ $s->nome }} ({{ $s->data_inizio->format('d/m/Y') }} – {{ $s->data_fine->format('d/m/Y') }})
                    </option>
                @empty
                    <option value="">Nessuna sessione disponibile</option>
                @endforelse
            </select>
        </div>
        <div class="col-auto">
            <noscript><button type="submit" class="btn btn-outline-secondary">Mostra</button></noscript>
        </div>
    </form>

    @unless ($isAdmin)
        <p class="text-muted small">
            <i class="bi bi-info-circle"></i> Degli appelli altrui sono visibili solo data, corso, anno e fascia oraria occupati.
        </p>
    @endunless

    @if ($sessioneSelezionata === null)
        <div class="alert alert-secondary">Nessuna sessione disponibile.</div>
    @elseif ($perData->isEmpty())
        <div class="alert alert-secondary">Nessun appello presente in questa sessione.</div>
    @else
        @foreach ($perData as $data => $appelliDelGiorno)
            <div class="card mb-3">
                <div class="card-header fw-semibold text-capitalize">
                    {{ \Illuminate\Support\Carbon::parse($data)->locale('it')->translatedFormat('l d/m/Y') }}
                </div>
                <div class="table-responsive">
                    <table class="table mb-0 align-middle">
                        <thead>
                            <tr>
                                <th style="width: 8rem;">Orario</th>
                                <th class="text-center" style="width: 6rem;">Anno</th>
                                <th>Corso</th>
                                <th>Insegnamento</th>
                                <th>Docente</th>
                                <th>Aula</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($appelliDelGiorno as $appello)
                                @php($visibile = $isAdmin || $appello->user_id === $userId || $insegnamentiIds->contains($appello->insegnamento_id))
                                @php($inConflitto = $idConflitto->contains($appello->id))
                                <tr class="{{ $inConflitto ? 'table-danger' : '' }} {{ $visibile ? '' : 'text-muted' }}">
                                    <td>
                                        {{ \Illuminate\Support\Str::substr($appello->ora_inizio, 0, 5) }}&ndash;{{ \Illuminate\Support\Str::substr($appello->ora_fine, 0, 5) }}
                                        @if ($inConflitto)
                                            <span class="badge text-bg-danger ms-1" title="In conflitto con un altro appello"><i class="bi bi-exclamation-triangle"></i> conflitto</span>
                                        @endif
                                        @if ($appello->data->lt($sessioneSelezionata->data_inizio))
                                            <span class="badge text-bg-info ms-1" title="Data precedente l'inizio della sessione">preappello</span>
                                        @endif
                                    </td>
                                    <td class="text-center">{{ $appello->insegnamento->anno_frequenza }}°</td>
                                    <td>{{ $appello->insegnamento->corsoStudio->nome }}</td>
                                    @if ($visibile)
                                        <td>
                                            {{ $appello->insegnamento->nome }}
                                            @if (! $isAdmin && $appello->user_id === $userId)
                                                <span class="badge text-bg-primary ms-1">tuo</span>
                                            @endif
                                        </td>
                                        <td>{{ $appello->docente->name }}</td>
                                        <td>{{ $appello->aula ?? '—' }}</td>
                                    @else
                                        <td colspan="3"><em>Occupato</em></td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach
    @endif
@endsection
