@extends('layouts.master')

@section('title', $sessione->nome)
@section('heading', $sessione->nome)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('struttura.index') }}">Struttura didattica</a></li>
    <li class="breadcrumb-item"><a href="{{ route('sessioni.index') }}">Sessioni</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $sessione->nome }}</li>
@endsection

@section('content')
    <div class="card mb-4">
        <div class="card-body">
            <p class="mb-1"><strong>Periodo della sessione:</strong>
                {{ $sessione->data_inizio->format('d/m/Y') }} &ndash; {{ $sessione->data_fine->format('d/m/Y') }}</p>
            <p class="text-muted mb-0">
                Le finestre di inserimento indicano i giorni in cui i docenti possono creare appelli per questa sessione.
            </p>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header fw-semibold">Finestre di inserimento</div>
        <div class="card-body p-0">
            @if ($sessione->periodiInserimento->isEmpty())
                <p class="text-muted m-3">Nessuna finestra di inserimento definita.</p>
            @else
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Dal</th>
                            <th>Al</th>
                            <th class="text-end">Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($sessione->periodiInserimento as $periodo)
                            <tr>
                                <td>{{ $periodo->data_inizio->format('d/m/Y') }}</td>
                                <td>{{ $periodo->data_fine->format('d/m/Y') }}</td>
                                <td class="text-end">
                                    <x-delete-form :action="route('periodi.destroy', [$sessione, $periodo])"
                                        message="Eliminare questa finestra di inserimento?" label="" />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>

    <div class="card">
        <div class="card-header fw-semibold">Aggiungi una finestra di inserimento</div>
        <div class="card-body">
            <form method="POST" action="{{ route('periodi.store', $sessione) }}">
                @csrf
                <div class="row g-3 align-items-end">
                    <div class="col-md-5">
                        <label for="data_inizio" class="form-label">Dal</label>
                        <input type="date" class="form-control" id="data_inizio" name="data_inizio"
                            value="{{ old('data_inizio') }}"
                            min="{{ $sessione->data_inizio->format('Y-m-d') }}"
                            max="{{ $sessione->data_fine->format('Y-m-d') }}" required>
                    </div>
                    <div class="col-md-5">
                        <label for="data_fine" class="form-label">Al</label>
                        <input type="date" class="form-control" id="data_fine" name="data_fine"
                            value="{{ old('data_fine') }}"
                            min="{{ $sessione->data_inizio->format('Y-m-d') }}"
                            max="{{ $sessione->data_fine->format('Y-m-d') }}" required>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">Aggiungi</button>
                    </div>
                </div>
                <p class="form-text mb-0 mt-2">La finestra deve rientrare nel periodo della sessione
                    ({{ $sessione->data_inizio->format('d/m/Y') }} &ndash; {{ $sessione->data_fine->format('d/m/Y') }}).</p>
            </form>
        </div>
    </div>
@endsection
