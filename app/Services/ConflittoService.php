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
     * Regola: due appelli sono in conflitto se gli insegnamenti appartengono
     * allo stesso corso di studio e allo stesso anno di frequenza, cadono nella
     * stessa data e le fasce orarie si sovrappongono (gli studenti coinvolti
     * sono gli stessi).
     *
     * @param  int|null  $escludiId  Id di un appello da escludere (utile in modifica).
     * @return Collection<int, Appello>
     */
    public function trovaConflitti(
        int $insegnamentoId,
        string $data,
        string $oraInizio,
        string $oraFine,
        ?int $escludiId = null
    ): Collection {
        $insegnamento = Insegnamento::find($insegnamentoId);

        if ($insegnamento === null) {
            return collect();
        }

        return Appello::with(['insegnamento.corsoStudio', 'docente'])
            ->whereDate('data', $data)
            ->when($escludiId !== null, fn ($q) => $q->where('id', '!=', $escludiId))
            ->whereHas('insegnamento', fn ($q) => $q
                ->where('anno_frequenza', $insegnamento->anno_frequenza)
                ->where('corso_studio_id', $insegnamento->corso_studio_id))
            // Sovrapposizione delle fasce: inizio < fine_altro AND fine > inizio_altro
            ->where('ora_inizio', '<', $oraFine)
            ->where('ora_fine', '>', $oraInizio)
            ->orderBy('ora_inizio')
            ->get();
    }
}
