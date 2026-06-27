@extends('layouts.master')

@section('title', $isAdmin ? 'Appelli' : 'I miei appelli')
@section('heading', $isAdmin ? 'Tutti gli appelli' : 'I miei appelli')

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">Appelli</li>
@endsection

@section('actions')
    <a href="{{ route('appelli.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Nuovo appello</a>
@endsection

@section('content')
    {{-- Filtro: tutti gli appelli oppure solo quelli in conflitto --}}
    @if ($totaleConflitti > 0)
        <div class="btn-group mb-3" role="group" aria-label="Filtro appelli">
            <a href="{{ route('appelli.index') }}"
                class="btn btn-outline-secondary {{ $soloConflitti ? '' : 'active' }}">Tutti</a>
            <a href="{{ route('appelli.index', ['conflitti' => 1]) }}"
                class="btn btn-outline-danger {{ $soloConflitti ? 'active' : '' }}">
                <i class="bi bi-exclamation-triangle"></i> Solo conflitti ({{ $totaleConflitti }})
            </a>
        </div>
    @endif

    <div class="card">
        <div class="card-body p-0">
            @if ($appelli->isEmpty())
                <p class="text-muted m-3">{{ $soloConflitti ? 'Nessun appello in conflitto.' : 'Nessun appello presente.' }}</p>
            @else
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Orario</th>
                            <th>Insegnamento</th>
                            <th class="text-center">Anno</th>
                            @if ($isAdmin)
                                <th>Docente</th>
                            @endif
                            <th>Sessione</th>
                            <th>Aula</th>
                            <th class="text-end">Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($appelli as $appello)
                            @php($inConflitto = $idConflitto->contains($appello->id))
                            <tr class="{{ $inConflitto ? 'table-danger' : '' }}">
                                <td>{{ $appello->data->format('d/m/Y') }}</td>
                                <td>
                                    {{ \Illuminate\Support\Str::substr($appello->ora_inizio, 0, 5) }}&ndash;{{ \Illuminate\Support\Str::substr($appello->ora_fine, 0, 5) }}
                                    @if ($inConflitto)
                                        <span class="badge text-bg-danger ms-1" title="In conflitto con un altro appello"><i class="bi bi-exclamation-triangle"></i> conflitto</span>
                                    @endif
                                </td>
                                <td>{{ $appello->insegnamento->nome }}</td>
                                <td class="text-center">{{ $appello->insegnamento->anno_frequenza }}°</td>
                                @if ($isAdmin)
                                    <td>{{ $appello->docente->name }}</td>
                                @endif
                                <td>{{ $appello->sessione->nome }}</td>
                                <td>{{ $appello->aula ?? '—' }}</td>
                                <td class="text-end">
                                    @if ($idModificabili->contains($appello->id))
                                        <a href="{{ route('appelli.edit', $appello) }}" class="btn btn-sm btn-outline-secondary">
                                            <i class="bi bi-pencil"></i> Modifica
                                        </a>
                                        <x-delete-form :action="route('appelli.destroy', $appello)"
                                            message="Eliminare questo appello di {{ $appello->insegnamento->nome }} del {{ $appello->data->format('d/m/Y') }}?" />
                                    @else
                                        <span class="text-muted small" title="Finestra di inserimento chiusa">
                                            <i class="bi bi-lock"></i> finestra chiusa
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
@endsection
