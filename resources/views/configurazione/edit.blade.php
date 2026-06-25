@extends('layouts.master')

@section('title', 'Configurazione conflitti')
@section('heading', 'Configurazione conflitti')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('struttura.index') }}">Struttura didattica</a></li>
    <li class="breadcrumb-item active" aria-current="page">Configurazione</li>
@endsection

@section('content')
    <div class="card" style="max-width: 40rem;">
        <div class="card-body">
            <p class="text-muted">
                Due appelli sono in conflitto quando riguardano insegnamenti dello <strong>stesso anno di
                frequenza</strong> e si sovrappongono nella stessa data e fascia oraria. Scegli come gestire i conflitti
                al momento del salvataggio di un appello.
            </p>

            <form method="POST" action="{{ route('configurazione.update') }}">
                @csrf
                @method('PUT')

                <div class="form-check mb-3">
                    <input class="form-check-input" type="radio" name="modalita_conflitto" id="modalita_blocco"
                        value="blocco" @checked(old('modalita_conflitto', $configurazione->modalita_conflitto) === 'blocco')>
                    <label class="form-check-label" for="modalita_blocco">
                        <strong>Blocco</strong> — impedisce il salvataggio in caso di conflitto.
                    </label>
                </div>

                <div class="form-check mb-3">
                    <input class="form-check-input" type="radio" name="modalita_conflitto" id="modalita_warning"
                        value="warning" @checked(old('modalita_conflitto', $configurazione->modalita_conflitto) === 'warning')>
                    <label class="form-check-label" for="modalita_warning">
                        <strong>Avviso</strong> — consente il salvataggio ma segnala il conflitto.
                    </label>
                </div>

                @error('modalita_conflitto')
                    <div class="text-danger small mb-3">{{ $message }}</div>
                @enderror

                <button type="submit" class="btn btn-primary">Salva impostazioni</button>
            </form>
        </div>
    </div>
@endsection
