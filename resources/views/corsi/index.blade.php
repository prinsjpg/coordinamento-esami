@extends('layouts.master')

@section('title', 'Corsi di studio')
@section('heading', 'Corsi di studio')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('struttura.index') }}">Struttura didattica</a></li>
    <li class="breadcrumb-item active" aria-current="page">Corsi di studio</li>
@endsection

@section('actions')
    <a href="{{ route('corsi.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Nuovo corso</a>
@endsection

@section('content')
    <div class="card">
        <div class="card-body p-0">
            @if ($corsi->isEmpty())
                <p class="text-muted m-3">Nessun corso di studio registrato.</p>
            @else
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th class="text-center">Insegnamenti</th>
                            <th class="text-end">Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($corsi as $corso)
                            <tr>
                                <td>{{ $corso->nome }}</td>
                                <td class="text-center">{{ $corso->insegnamenti_count }}</td>
                                <td class="text-end">
                                    <a href="{{ route('corsi.edit', $corso) }}" class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-pencil"></i> Modifica
                                    </a>
                                    @php
                                        $parti = [];
                                        if ($corso->insegnamenti_count > 0) {
                                            $parti[] = $corso->insegnamenti_count . ' ' . ($corso->insegnamenti_count === 1 ? 'insegnamento' : 'insegnamenti');
                                        }
                                        if ($corso->appelli_count > 0) {
                                            $parti[] = $corso->appelli_count . ' ' . ($corso->appelli_count === 1 ? 'appello' : 'appelli');
                                        }
                                        $msg = "Eliminare il corso «{$corso->nome}»?";
                                        if ($parti) {
                                            $msg .= ' Verranno eliminati anche ' . implode(' e ', $parti) . '.';
                                        }
                                    @endphp
                                    <x-delete-form :action="route('corsi.destroy', $corso)" :message="$msg" />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
@endsection
