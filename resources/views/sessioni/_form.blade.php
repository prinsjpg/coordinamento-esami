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
            value="{{ old('data_inizio', $sessione->data_inizio?->format('Y-m-d')) }}" required>
        @error('data_inizio')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="data_fine" class="form-label">Data di fine</label>
        <input type="date" class="form-control @error('data_fine') is-invalid @enderror" id="data_fine" name="data_fine"
            value="{{ old('data_fine', $sessione->data_fine?->format('Y-m-d')) }}" required>
        @error('data_fine')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="d-flex gap-2 mt-4">
    <button type="submit" class="btn btn-primary">Salva</button>
    <a href="{{ route('sessioni.index') }}" class="btn btn-outline-secondary">Annulla</a>
</div>
