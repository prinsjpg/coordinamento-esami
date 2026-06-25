<div class="row g-3">
    <div class="col-md-8">
        <label for="nome" class="form-label">Nome dell'insegnamento</label>
        <input type="text" class="form-control @error('nome') is-invalid @enderror" id="nome" name="nome"
            value="{{ old('nome', $insegnamento->nome) }}" required>
        @error('nome')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="anno_frequenza" class="form-label">Anno di frequenza</label>
        <select class="form-select @error('anno_frequenza') is-invalid @enderror" id="anno_frequenza" name="anno_frequenza" required>
            @foreach ([1, 2, 3] as $anno)
                <option value="{{ $anno }}" @selected((int) old('anno_frequenza', $insegnamento->anno_frequenza) === $anno)>
                    {{ $anno }}° anno
                </option>
            @endforeach
        </select>
        @error('anno_frequenza')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12">
        <label for="corso_studio_id" class="form-label">Corso di studio</label>
        <select class="form-select @error('corso_studio_id') is-invalid @enderror" id="corso_studio_id" name="corso_studio_id" required>
            <option value="">— Seleziona un corso —</option>
            @foreach ($corsi as $corso)
                <option value="{{ $corso->id }}" @selected((int) old('corso_studio_id', $insegnamento->corso_studio_id) === $corso->id)>
                    {{ $corso->nome }}
                </option>
            @endforeach
        </select>
        @error('corso_studio_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12">
        <label for="docenti" class="form-label">Docenti titolari</label>
        <select class="form-select @error('docenti') is-invalid @enderror" id="docenti" name="docenti[]" multiple size="5">
            @foreach ($docenti as $docente)
                <option value="{{ $docente->id }}" @selected(in_array($docente->id, old('docenti', $docentiSelezionati)))>
                    {{ $docente->name }} ({{ $docente->email }})
                </option>
            @endforeach
        </select>
        <div class="form-text">Tieni premuto Ctrl (Cmd su Mac) per selezionare più docenti.</div>
        @error('docenti')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="d-flex gap-2 mt-4">
    <button type="submit" class="btn btn-primary">Salva</button>
    <a href="{{ route('insegnamenti.index') }}" class="btn btn-outline-secondary">Annulla</a>
</div>
