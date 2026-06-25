@extends('layouts.master')

@section('title', 'Importa CSV')
@section('heading', 'Importa struttura didattica (CSV)')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('struttura.index') }}">Struttura didattica</a></li>
    <li class="breadcrumb-item active" aria-current="page">Importa CSV</li>
@endsection

@section('content')

    {{-- Esito dell'import: righe con errori --}}
    @if (session('import_errori'))
        <div class="alert alert-danger">
            <strong>Import non eseguito.</strong> Sono stati rilevati errori nelle righe seguenti; correggi il file e
            riprova (non è stato importato nulla).
            <table class="table table-sm mt-2 mb-0">
                <thead>
                    <tr><th style="width: 6rem;">Riga</th><th>Problema</th></tr>
                </thead>
                <tbody>
                    @foreach (session('import_errori') as $errore)
                        <tr>
                            <td>{{ $errore['riga'] }}</td>
                            <td>{{ $errore['messaggio'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{-- Esito dell'import: riepilogo positivo --}}
    @if (session('import_riepilogo'))
        @php($r = session('import_riepilogo'))
        <div class="alert alert-success">
            <strong>Import completato.</strong>
            <ul class="mb-0 mt-1">
                <li>Corsi creati: {{ $r['corsi_creati'] }}</li>
                <li>Insegnamenti creati: {{ $r['insegnamenti_creati'] }}</li>
                <li>Insegnamenti aggiornati: {{ $r['insegnamenti_aggiornati'] }}</li>
                <li>Associazioni docente-insegnamento: {{ $r['associazioni'] }}</li>
            </ul>
        </div>
    @endif

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header fw-semibold">Carica un file CSV</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('import.store') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label for="file" class="form-label">File CSV</label>
                            <input type="file" class="form-control @error('file') is-invalid @enderror"
                                id="file" name="file" accept=".csv,text/csv" required>
                            @error('file')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-upload"></i> Importa</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card">
                <div class="card-header fw-semibold">Formato richiesto</div>
                <div class="card-body">
                    <p class="mb-2">Il file deve avere una riga di intestazione con le colonne:</p>
                    <ul class="small">
                        <li><code>corso</code> — nome del corso di studio</li>
                        <li><code>insegnamento</code> — nome dell'insegnamento</li>
                        <li><code>anno_frequenza</code> — numero da 1 a 3</li>
                        <li><code>docenti</code> (facoltativo) — email dei docenti già registrati, separate da <code>|</code></li>
                    </ul>
                    <p class="small text-muted mb-3">
                        Separatore di colonna: virgola o punto e virgola (rilevato automaticamente). Corsi e insegnamenti
                        già presenti vengono aggiornati anziché duplicati.
                    </p>
                    <a href="{{ route('import.template') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-download"></i> Scarica CSV di esempio
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection
