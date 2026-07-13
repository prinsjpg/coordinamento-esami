<div class="row g-3">
    <div class="col-12">
        <label for="nome" class="form-label">Nome della sessione</label>
        <input type="text" class="form-control @error('nome') is-invalid @enderror" id="nome" name="nome"
            value="{{ old('nome', $sessione->nome) }}" required>
        @error('nome')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="data_inizio" class="form-label">Data di inizio</label>
        <input type="date" class="form-control @error('data_inizio') is-invalid @enderror" id="data_inizio" name="data_inizio"
            value="{{ old('data_inizio', $sessione->data_inizio?->format('Y-m-d')) }}"
            min="{{ now()->addDay()->format('Y-m-d') }}" required>
        <div class="form-text">Deve iniziare da domani in poi.</div>
        @error('data_inizio')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="data_fine" class="form-label">Data di fine</label>
        <input type="date" class="form-control @error('data_fine') is-invalid @enderror" id="data_fine" name="data_fine"
            value="{{ old('data_fine', $sessione->data_fine?->format('Y-m-d')) }}"
            min="{{ now()->addDays(7)->format('Y-m-d') }}" required>
        <div class="form-text">La sessione deve durare almeno una settimana.</div>
        @error('data_fine')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

@push('scripts')
<script>
    // Il minimo della data di fine segue la data di inizio scelta: una settimana
    // dopo, estremi inclusi. Il vincolo vero resta comunque lato server.
    $(function () {
        const $inizio = $('#data_inizio');
        const $fine = $('#data_fine');

        $inizio.on('change', function () {
            if (!this.value) {
                return;
            }

            const minimo = new Date(this.value);
            minimo.setDate(minimo.getDate() + 6);
            $fine.attr('min', minimo.toISOString().slice(0, 10));
        }).trigger('change');
    });
</script>
@endpush

<div class="d-flex gap-2 mt-4">
    <button type="submit" class="btn btn-primary">Salva</button>
    <a href="{{ route('sessioni.index') }}" class="btn btn-outline-secondary">Annulla</a>
</div>
