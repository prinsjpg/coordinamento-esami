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
            <p class="text-muted mb-2">
                Due appelli sono in conflitto quando cadono nella <strong>stessa data</strong>, le
                <strong>fasce orarie si sovrappongono</strong> e si verifica almeno una di queste condizioni:
            </p>
            <ul class="text-muted">
                <li><strong>conflitto studenti</strong> — gli insegnamenti appartengono allo <strong>stesso corso di
                    studio</strong> e allo <strong>stesso anno di frequenza</strong>;</li>
                <li><strong>conflitto aula</strong> — risulterebbe occupata la <strong>stessa aula</strong>.</li>
            </ul>
            <p class="text-muted">Scegli come gestire i conflitti al momento del salvataggio di un appello.</p>

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

                <hr>

                <div class="mb-3">
                    <label for="giorni_preappello" class="form-label fw-semibold">Preappelli</label>
                    <p class="text-muted small mb-2">
                        Giorni di anticipo entro cui un docente può fissare un appello <strong>prima</strong> dell'inizio
                        della sessione, così gli studenti possono organizzarsi per lo studio. Imposta 0 per non ammettere preappelli.
                    </p>
                    <div class="input-group" style="max-width: 16rem;">
                        <input type="number" class="form-control @error('giorni_preappello') is-invalid @enderror"
                            id="giorni_preappello" name="giorni_preappello" min="0" max="60"
                            value="{{ old('giorni_preappello', $configurazione->giorni_preappello) }}" required>
                        <span class="input-group-text">giorni</span>
                    </div>
                    @error('giorni_preappello')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <button type="submit" class="btn btn-primary">Salva impostazioni</button>
            </form>
        </div>
    </div>
@endsection
