<div class="mb-3">
    <label for="nome" class="form-label">Nome del corso</label>
    <input type="text" class="form-control @error('nome') is-invalid @enderror" id="nome" name="nome"
        value="{{ old('nome', $corso->nome) }}" required>
    @error('nome')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="d-flex gap-2">
    <button type="submit" class="btn btn-primary">Salva</button>
    <a href="{{ route('corsi.index') }}" class="btn btn-outline-secondary">Annulla</a>
</div>
