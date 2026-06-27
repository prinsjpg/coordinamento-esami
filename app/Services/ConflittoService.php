<?php

namespace App\Services;

use App\Models\Appello;
use App\Models\Insegnamento;
use Illuminate\Support\Collection;

class ConflittoService
{
    /**
     * Trova gli appelli in conflitto con quello descritto dai parametri.
     *
     * Due appelli sono in conflitto quando cadono nella stessa data, le fasce
     * orarie si sovrappongono e si verifica almeno una di queste condizioni:
     *  - conflitto "studenti": stesso corso di studio e stesso anno di frequenza
     *    (gli studenti coinvolti sarebbero gli stessi);
     *  - conflitto "aula": la stessa aula risulterebbe occupata due volte.
     *
     * @param  string|null  $aula       Aula del nuovo appello (per il conflitto sull'aula).
     * @param  int|null      $escludiId  Id di un appello da escludere (utile in modifica).
     * @return Collection<int, Appello>
     */
    public function trovaConflitti(
        int $insegnamentoId,
        string $data,
        string $oraInizio,
        string $oraFine,
        ?string $aula = null,
        ?int $escludiId = null
    ): Collection {
        $insegnamento = Insegnamento::find($insegnamentoId);

        if ($insegnamento === null) {
            return collect();
        }

        $aula = $aula !== null ? trim($aula) : '';

        return Appello::with(['insegnamento.corsoStudio', 'docente'])
            ->whereDate('data', $data)
            ->when($escludiId !== null, fn ($q) => $q->where('id', '!=', $escludiId))
            // Sovrapposizione delle fasce: inizio < fine_altro AND fine > inizio_altro
            ->where('ora_inizio', '<', $oraFine)
            ->where('ora_fine', '>', $oraInizio)
            ->where(function ($q) use ($insegnamento, $aula) {
                // Conflitto "studenti": stesso corso e stesso anno
                $q->whereHas('insegnamento', fn ($s) => $s
                    ->where('anno_frequenza', $insegnamento->anno_frequenza)
                    ->where('corso_studio_id', $insegnamento->corso_studio_id));

                // Conflitto "aula": stessa aula (confronto senza spazi/maiuscole)
                if ($aula !== '') {
                    $q->orWhereRaw('LOWER(TRIM(aula)) = ?', [mb_strtolower($aula)]);
                }
            })
            ->orderBy('ora_inizio')
            ->get();
    }

    /**
     * Dato un insieme di appelli già caricati, restituisce gli id di quelli in
     * conflitto con almeno un altro appello. Utile per evidenziare i conflitti
     * già presenti (es. salvati in modalità «avviso») nel calendario e
     * nell'elenco, senza interrogare di nuovo il database.
     *
     * Il confronto avviene contro `$universo`: se omesso coincide con `$appelli`
     * (il caso dell'admin, che vede tutto). Per il docente, che vede solo i
     * propri appelli, va passato l'insieme completo come universo, così da
     * rilevare anche i conflitti con appelli di altri docenti (es. stessa aula).
     *
     * Richiede che la relazione `insegnamento` sia già caricata su entrambi.
     *
     * @param  Collection<int, Appello>       $appelli  appelli da valutare
     * @param  Collection<int, Appello>|null  $universo  insieme contro cui confrontare
     * @return Collection<int, int>  id degli appelli in conflitto
     */
    public function idInConflitto(Collection $appelli, ?Collection $universo = null): Collection
    {
        $universo = ($universo ?? $appelli)->values();
        $inConflitto = [];

        foreach ($appelli as $a) {
            foreach ($universo as $b) {
                if ($a->id === $b->id) {
                    continue;
                }
                if ($this->sonoInConflitto($a, $b)) {
                    $inConflitto[$a->id] = true;
                    break;
                }
            }
        }

        return collect(array_keys($inConflitto));
    }

    /**
     * Due appelli sono in conflitto se cadono nello stesso giorno, le fasce si
     * sovrappongono e condividono studenti (stesso corso e anno) oppure l'aula.
     */
    private function sonoInConflitto(Appello $a, Appello $b): bool
    {
        if (! $a->data->isSameDay($b->data)) {
            return false;
        }

        $inizioA = mb_substr((string) $a->ora_inizio, 0, 5);
        $fineA = mb_substr((string) $a->ora_fine, 0, 5);
        $inizioB = mb_substr((string) $b->ora_inizio, 0, 5);
        $fineB = mb_substr((string) $b->ora_fine, 0, 5);

        if (! ($inizioA < $fineB && $fineA > $inizioB)) {
            return false;
        }

        $stessiStudenti = (int) $a->insegnamento->anno_frequenza === (int) $b->insegnamento->anno_frequenza
            && (int) $a->insegnamento->corso_studio_id === (int) $b->insegnamento->corso_studio_id;

        $aulaA = mb_strtolower(trim((string) $a->aula));
        $stessaAula = $aulaA !== '' && $aulaA === mb_strtolower(trim((string) $b->aula));

        return $stessiStudenti || $stessaAula;
    }
}
