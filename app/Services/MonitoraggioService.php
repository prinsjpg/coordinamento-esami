<?php

namespace App\Services;

use App\Models\Insegnamento;
use App\Models\Sessione;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Monitora il rispetto delle scadenze di inserimento degli appelli e produce
 * le segnalazioni richieste dalla traccia: quali insegnamenti restano senza
 * appello mentre la finestra di inserimento è in scadenza o già chiusa.
 */
class MonitoraggioService
{
    /** Soglia (in giorni) entro cui una finestra ancora aperta è "in scadenza". */
    private const GIORNI_SCADENZA = 7;

    /**
     * Sessioni il cui periodo d'esame non è ancora concluso: sono le uniche per
     * cui ha senso monitorare gli appelli mancanti.
     *
     * @return Collection<int, Sessione>
     */
    public function sessioniDaMonitorare(): Collection
    {
        return Sessione::with('periodiInserimento')
            ->whereDate('data_fine', '>=', Carbon::today())
            ->orderBy('data_inizio')
            ->get();
    }

    /**
     * Stato della finestra di inserimento di una sessione.
     *
     * @return string  non_definita | non_aperta | aperta | in_scadenza | chiusa
     */
    public function statoFinestra(Sessione $sessione): string
    {
        $oggi = Carbon::today();
        $periodi = $sessione->periodiInserimento;

        if ($periodi->isEmpty()) {
            return 'non_definita';
        }

        $aperto = $periodi->first(fn ($p) => $oggi->between($p->data_inizio, $p->data_fine));

        if ($aperto !== null) {
            return $oggi->diffInDays($aperto->data_fine) <= self::GIORNI_SCADENZA ? 'in_scadenza' : 'aperta';
        }

        // Nessun periodo aperto oggi: "non ancora aperta" se ne esiste uno futuro,
        // altrimenti la finestra è "chiusa".
        $aperturaFutura = $periodi->contains(fn ($p) => $p->data_inizio->gt($oggi));

        return $aperturaFutura ? 'non_aperta' : 'chiusa';
    }

    /**
     * Insegnamenti privi di appello nella sessione indicata. Se è passato un
     * docente, ci si limita ai suoi insegnamenti.
     *
     * @return Collection<int, Insegnamento>
     */
    public function insegnamentiMancanti(Sessione $sessione, ?User $docente = null): Collection
    {
        $query = $docente !== null ? $docente->insegnamenti() : Insegnamento::query();

        return $query->with(['corsoStudio', 'docenti'])
            ->whereDoesntHave('appelli', fn ($q) => $q->where('sessione_id', $sessione->id))
            ->orderBy('nome')
            ->get();
    }

    /**
     * Segnalazioni per l'amministratore: insegnamenti senza appello nelle
     * sessioni la cui finestra è in scadenza o già chiusa.
     *
     * @return Collection<int, array{sessione: Sessione, stato: string, insegnamenti: Collection<int, Insegnamento>}>
     */
    public function segnalazioniAdmin(): Collection
    {
        return $this->componiSegnalazioni(null, ['in_scadenza', 'chiusa']);
    }

    /**
     * Segnalazioni per il docente: i propri insegnamenti ancora senza appello
     * nelle sessioni con finestra aperta, in scadenza o appena chiusa.
     *
     * @return Collection<int, array{sessione: Sessione, stato: string, insegnamenti: Collection<int, Insegnamento>}>
     */
    public function segnalazioniDocente(User $docente): Collection
    {
        return $this->componiSegnalazioni($docente, ['aperta', 'in_scadenza', 'chiusa']);
    }

    /**
     * Costruisce l'elenco delle segnalazioni filtrando per stato della finestra
     * e tenendo solo le sessioni con almeno un insegnamento mancante.
     *
     * @param  array<int, string>  $statiRilevanti
     * @return Collection<int, array{sessione: Sessione, stato: string, insegnamenti: Collection<int, Insegnamento>}>
     */
    private function componiSegnalazioni(?User $docente, array $statiRilevanti): Collection
    {
        return $this->sessioniDaMonitorare()
            ->map(fn (Sessione $s) => [
                'sessione' => $s,
                'stato' => $this->statoFinestra($s),
                'insegnamenti' => $this->insegnamentiMancanti($s, $docente),
            ])
            ->filter(fn ($r) => in_array($r['stato'], $statiRilevanti, true) && $r['insegnamenti']->isNotEmpty())
            ->values();
    }
}
