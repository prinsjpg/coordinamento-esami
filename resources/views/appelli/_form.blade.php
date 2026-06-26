@php
    use Illuminate\Support\Str;
@endphp

<div class="row g-3">
    <div class="col-md-8">
        <label for="insegnamento_id" class="form-label">Insegnamento</label>
        <select class="form-select @error('insegnamento_id') is-invalid @enderror" id="insegnamento_id" name="insegnamento_id" required>
            <option value="">— Seleziona un insegnamento —</option>
            @foreach ($insegnamenti as $ins)
                <option value="{{ $ins->id }}" @selected((int) old('insegnamento_id', $appello->insegnamento_id) === $ins->id)>
                    {{ $ins->nome }} ({{ $ins->corsoStudio->nome }} — {{ $ins->anno_frequenza }}° anno)
                </option>
            @endforeach
        </select>
        @error('insegnamento_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        @if ($insegnamenti->isEmpty())
            <div class="form-text text-danger">Non hai insegnamenti assegnati: contatta l'amministratore.</div>
        @endif
    </div>

    <div class="col-md-4">
        <label for="sessione_id" class="form-label">Sessione</label>
        <select class="form-select @error('sessione_id') is-invalid @enderror" id="sessione_id" name="sessione_id" required>
            <option value="">— Seleziona —</option>
            @foreach ($sessioni as $sessione)
                <option value="{{ $sessione->id }}" @selected((int) old('sessione_id', $appello->sessione_id) === $sessione->id)>
                    {{ $sessione->nome }}
                </option>
            @endforeach
        </select>
        @error('sessione_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="data" class="form-label">Data</label>
        <input type="date" class="form-control @error('data') is-invalid @enderror" id="data" name="data"
            value="{{ old('data', $appello->data?->format('Y-m-d')) }}" required>
        @error('data')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="ora_inizio" class="form-label">Ora di inizio</label>
        <input type="time" class="form-control @error('ora_inizio') is-invalid @enderror" id="ora_inizio" name="ora_inizio"
            value="{{ old('ora_inizio', Str::substr((string) $appello->ora_inizio, 0, 5)) }}" required>
        @error('ora_inizio')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="ora_fine" class="form-label">Ora di fine</label>
        <input type="time" class="form-control @error('ora_fine') is-invalid @enderror" id="ora_fine" name="ora_fine"
            value="{{ old('ora_fine', Str::substr((string) $appello->ora_fine, 0, 5)) }}" required>
        @error('ora_fine')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="aula" class="form-label">Aula <span class="text-muted">(facoltativa)</span></label>
        <input type="text" class="form-control @error('aula') is-invalid @enderror" id="aula" name="aula"
            value="{{ old('aula', $appello->aula) }}">
        @error('aula')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12">
        <label for="note" class="form-label">Note <span class="text-muted">(facoltative)</span></label>
        <textarea class="form-control @error('note') is-invalid @enderror" id="note" name="note" rows="2">{{ old('note', $appello->note) }}</textarea>
        @error('note')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

{{-- Avviso giorno non lavorativo (weekend) e esito conflitti in tempo reale --}}
<input type="hidden" id="appello_id" value="{{ $appello->id }}">
<div id="avviso-data" class="mt-3"></div>
<div id="avviso-conflitto" class="mt-3"></div>

<div class="d-flex gap-2 mt-4">
    <button type="submit" class="btn btn-primary">Salva</button>
    <a href="{{ route('appelli.index') }}" class="btn btn-outline-secondary">Annulla</a>
</div>

@push('scripts')
<script>
    $(function () {
        const url = "{{ route('appelli.verifica-conflitto') }}";
        const $box = $('#avviso-conflitto');
        const $campi = $('#insegnamento_id, #data, #ora_inizio, #ora_fine, #aula');

        function etichettaMotivi(motivi) {
            const m = [];
            if (motivi && motivi.indexOf('studenti') !== -1) m.push('stesso corso e anno');
            if (motivi && motivi.indexOf('aula') !== -1) m.push('stessa aula');
            return m.length ? ' <span class="text-muted">[' + m.join(', ') + ']</span>' : '';
        }

        function verificaConflitto() {
            const insegnamento = $('#insegnamento_id').val();
            const data = $('#data').val();
            const oraInizio = $('#ora_inizio').val();
            const oraFine = $('#ora_fine').val();

            // Serve tutto compilato e una fascia coerente
            if (!insegnamento || !data || !oraInizio || !oraFine || oraFine <= oraInizio) {
                $box.empty();
                return;
            }

            $.getJSON(url, {
                insegnamento_id: insegnamento,
                data: data,
                ora_inizio: oraInizio,
                ora_fine: oraFine,
                aula: $('#aula').val() || '',
                appello_id: $('#appello_id').val() || ''
            }).done(function (res) {
                if (!res.conflitto) {
                    $box.html('<div class="alert alert-success mb-0 py-2"><i class="bi bi-check-circle"></i> Nessun conflitto rilevato.</div>');
                    return;
                }

                const righe = res.dettagli.map(function (d) {
                    const motivo = etichettaMotivi(d.motivi);
                    return d.insegnamento
                        ? '<li>' + d.insegnamento + ' — ' + d.docente + ' (' + d.orario + ')' + motivo + '</li>'
                        : '<li>' + d.anno + '° anno, fascia ' + d.orario + motivo + '</li>';
                }).join('');

                $box.html(
                    '<div class="alert alert-warning mb-0">' +
                    '<strong><i class="bi bi-exclamation-triangle"></i> Conflitto rilevato</strong> ' +
                    'con ' + res.numero + ' appello/i nella stessa data e fascia oraria:' +
                    '<ul class="mb-0 mt-1">' + righe + '</ul></div>'
                );
            }).fail(function () {
                $box.empty();
            });
        }

        // Avviso immediato se la data scelta cade nel weekend
        const $avvisoData = $('#avviso-data');

        function verificaWeekend() {
            const valore = $('#data').val();
            if (!valore) { $avvisoData.empty(); return; }

            const parti = valore.split('-');
            const giorno = new Date(parti[0], parti[1] - 1, parti[2]).getDay();

            if (giorno === 0 || giorno === 6) {
                $avvisoData.html('<div class="alert alert-warning mb-0 py-2"><i class="bi bi-exclamation-triangle"></i> La data scelta cade di sabato o domenica: non è possibile fissare un appello nel weekend.</div>');
            } else {
                $avvisoData.empty();
            }
        }

        $('#data').on('change', verificaWeekend);
        $campi.on('change', verificaConflitto);

        // Verifica anche allo apertura, se il form è già compilato (modifica)
        verificaWeekend();
        verificaConflitto();
    });
</script>
@endpush
