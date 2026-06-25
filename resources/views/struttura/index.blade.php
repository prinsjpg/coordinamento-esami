@extends('layouts.master')

@section('title', 'Struttura didattica')
@section('heading', 'Struttura didattica')

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">Struttura didattica</li>
@endsection

@section('content')
    <p class="text-muted">Gestisci corsi di studio, insegnamenti, sessioni d'esame e le impostazioni dei conflitti.</p>

    <div class="row g-4">
        <div class="col-md-6 col-lg-3">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-mortarboard me-1"></i> Corsi di studio</h5>
                    <p class="card-text text-muted">{{ $corsi }} corsi registrati.</p>
                    <a href="{{ route('corsi.index') }}" class="btn btn-primary btn-sm">Gestisci</a>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-journal-text me-1"></i> Insegnamenti</h5>
                    <p class="card-text text-muted">{{ $insegnamenti }} insegnamenti registrati.</p>
                    <a href="{{ route('insegnamenti.index') }}" class="btn btn-primary btn-sm">Gestisci</a>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-calendar-range me-1"></i> Sessioni</h5>
                    <p class="card-text text-muted">{{ $sessioni }} sessioni registrate.</p>
                    <a href="{{ route('sessioni.index') }}" class="btn btn-primary btn-sm">Gestisci</a>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-gear me-1"></i> Conflitti</h5>
                    <p class="card-text text-muted">Modalità attuale: <strong>{{ ucfirst($modalitaConflitto) }}</strong>.</p>
                    <a href="{{ route('configurazione.edit') }}" class="btn btn-primary btn-sm">Configura</a>
                </div>
            </div>
        </div>
    </div>
@endsection
