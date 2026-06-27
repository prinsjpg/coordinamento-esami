@extends('layouts.master')

@section('title', 'Insegnamenti')
@section('heading', 'Insegnamenti')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('struttura.index') }}">Struttura didattica</a></li>
    <li class="breadcrumb-item active" aria-current="page">Insegnamenti</li>
@endsection

@section('actions')
    <a href="{{ route('insegnamenti.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Nuovo insegnamento</a>
@endsection

@section('content')
    @php
        $filtriAttivi = $filtri['corso'] || $filtri['anno'] || $filtri['q'];
    @endphp

    {{-- Barra dei filtri (invio in GET, auto-applicazione al cambio) --}}
    <form method="GET" action="{{ route('insegnamenti.index') }}" class="row g-2 align-items-end mb-3">
        <div class="col-sm-4">
            <label for="corso" class="form-label">Corso di studio</label>
            <select name="corso" id="corso" class="form-select" onchange="this.form.submit()">
                <option value="">Tutti i corsi</option>
                @foreach ($corsi as $corso)
                    <option value="{{ $corso->id }}" @selected((int) $filtri['corso'] === $corso->id)>{{ $corso->nome }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-sm-2">
            <label for="anno" class="form-label">Anno</label>
            <select name="anno" id="anno" class="form-select" onchange="this.form.submit()">
                <option value="">Tutti</option>
                @for ($a = 1; $a <= 3; $a++)
                    <option value="{{ $a }}" @selected((int) $filtri['anno'] === $a)>{{ $a }}° anno</option>
                @endfor
            </select>
        </div>
        <div class="col-sm-4">
            <label for="q" class="form-label">Cerca per nome</label>
            <input type="text" name="q" id="q" class="form-control" value="{{ $filtri['q'] }}" placeholder="Nome insegnamento…">
        </div>
        <div class="col-sm-2 d-flex gap-2">
            <button type="submit" class="btn btn-outline-secondary w-100">Filtra</button>
            @if ($filtriAttivi)
                <a href="{{ route('insegnamenti.index') }}" class="btn btn-outline-secondary" title="Azzera filtri"><i class="bi bi-x-lg"></i></a>
            @endif
        </div>
    </form>

    <div class="card">
        <div class="card-body p-0">
            @if ($insegnamenti->isEmpty())
                <p class="text-muted m-3">
                    {{ $filtriAttivi ? 'Nessun insegnamento corrisponde ai filtri.' : 'Nessun insegnamento registrato.' }}
                </p>
            @else
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th class="text-center">Anno</th>
                            <th>Docenti</th>
                            <th class="text-end">Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- Raggruppamento per corso di studio (gruppi in ordine alfabetico) --}}
                        @php
                            $gruppi = $insegnamenti->groupBy('corsoStudio.nome')->sortKeys();
                        @endphp
                        @foreach ($gruppi as $nomeCorso => $gruppo)
                            <tr class="table-light">
                                <th colspan="4">
                                    <i class="bi bi-mortarboard"></i> {{ $nomeCorso }}
                                    <span class="badge text-bg-secondary ms-1">{{ $gruppo->count() }}</span>
                                </th>
                            </tr>
                            @foreach ($gruppo as $insegnamento)
                                <tr>
                                    <td>{{ $insegnamento->nome }}</td>
                                    <td class="text-center">{{ $insegnamento->anno_frequenza }}°</td>
                                    <td>
                                        @forelse ($insegnamento->docenti as $docente)
                                            <span class="badge text-bg-light border">{{ $docente->name }}</span>
                                        @empty
                                            <span class="text-muted">—</span>
                                        @endforelse
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('insegnamenti.edit', $insegnamento) }}" class="btn btn-sm btn-outline-secondary">
                                            <i class="bi bi-pencil"></i> Modifica
                                        </a>
                                        @php
                                            $msg = "Eliminare l'insegnamento «{$insegnamento->nome}»?";
                                            if ($insegnamento->appelli_count > 0) {
                                                $msg .= ' Verranno eliminati anche ' . $insegnamento->appelli_count . ' '
                                                    . ($insegnamento->appelli_count === 1 ? 'appello' : 'appelli') . '.';
                                            }
                                        @endphp
                                        <x-delete-form :action="route('insegnamenti.destroy', $insegnamento)" :message="$msg" />
                                    </td>
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
@endsection
