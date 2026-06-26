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
    <div class="card">
        <div class="card-body p-0">
            @if ($insegnamenti->isEmpty())
                <p class="text-muted m-3">Nessun insegnamento registrato.</p>
            @else
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Corso</th>
                            <th class="text-center">Anno</th>
                            <th>Docenti</th>
                            <th class="text-end">Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($insegnamenti as $insegnamento)
                            <tr>
                                <td>{{ $insegnamento->nome }}</td>
                                <td>{{ $insegnamento->corsoStudio->nome }}</td>
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
                    </tbody>
                </table>
            @endif
        </div>
    </div>
@endsection
