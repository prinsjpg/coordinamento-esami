@extends('layouts.master')

@section('title', 'Sessioni')
@section('heading', "Sessioni d'esame")

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('struttura.index') }}">Struttura didattica</a></li>
    <li class="breadcrumb-item active" aria-current="page">Sessioni</li>
@endsection

@section('actions')
    <a href="{{ route('sessioni.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Nuova sessione</a>
@endsection

@section('content')
    <div class="card">
        <div class="card-body p-0">
            @if ($sessioni->isEmpty())
                <p class="text-muted m-3">Nessuna sessione registrata.</p>
            @else
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Periodo</th>
                            <th class="text-center">Finestre inserimento</th>
                            <th class="text-center">Appelli</th>
                            <th class="text-end">Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($sessioni as $sessione)
                            <tr>
                                <td>{{ $sessione->nome }}</td>
                                <td>{{ $sessione->data_inizio->format('d/m/Y') }} &ndash; {{ $sessione->data_fine->format('d/m/Y') }}</td>
                                <td class="text-center">{{ $sessione->periodi_inserimento_count }}</td>
                                <td class="text-center">{{ $sessione->appelli_count }}</td>
                                <td class="text-end">
                                    <a href="{{ route('sessioni.show', $sessione) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i> Dettagli
                                    </a>
                                    <a href="{{ route('sessioni.edit', $sessione) }}" class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-pencil"></i> Modifica
                                    </a>
                                    @php
                                        $parti = [];
                                        if ($sessione->periodi_inserimento_count > 0) {
                                            $parti[] = $sessione->periodi_inserimento_count . ' '
                                                . ($sessione->periodi_inserimento_count === 1 ? 'finestra di inserimento' : 'finestre di inserimento');
                                        }
                                        if ($sessione->appelli_count > 0) {
                                            $parti[] = $sessione->appelli_count . ' ' . ($sessione->appelli_count === 1 ? 'appello' : 'appelli');
                                        }
                                        $msg = "Eliminare la sessione «{$sessione->nome}»?";
                                        if ($parti) {
                                            $msg .= ' Verranno eliminati anche ' . implode(' e ', $parti) . '.';
                                        }
                                    @endphp
                                    <x-delete-form :action="route('sessioni.destroy', $sessione)" :message="$msg" />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
@endsection
