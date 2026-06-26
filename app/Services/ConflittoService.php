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
}
