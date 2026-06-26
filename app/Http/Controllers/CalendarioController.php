<?php

namespace App\Http\Controllers;

use App\Models\Appello;
use App\Models\Sessione;
use App\Services\ConflittoService;
use Illuminate\Http\Request;

class CalendarioController extends Controller
{
    /**
     * Mostra il calendario degli appelli di una sessione, con visibilità
     * differenziata: l'amministratore (e il docente sui propri appelli) vede
     * tutti i dettagli, mentre degli appelli altrui il docente vede solo
     * la data e l'anno di frequenza occupati.
     */
    public function index(Request $request, ConflittoService $conflitti)
    {
        $user = $request->user();
        $isAdmin = $user->hasRole('amministratore');

        $sessioni = Sessione::orderByDesc('data_inizio')->get();

        // Sessione selezionata: da query string oppure la più recente
        $sessioneSelezionata = $request->filled('sessione')
            ? $sessioni->firstWhere('id', (int) $request->input('sessione'))
            : $sessioni->first();

        $perData = collect();
        $idConflitto = collect();

        if ($sessioneSelezionata !== null) {
            $appelli = Appello::with(['insegnamento.corsoStudio', 'docente'])
                ->where('sessione_id', $sessioneSelezionata->id)
                ->orderBy('data')->orderBy('ora_inizio')
                ->get();

            $idConflitto = $conflitti->idInConflitto($appelli);
            $perData = $appelli->groupBy(fn (Appello $a) => $a->data->format('Y-m-d'));
        }

        // Insegnamenti del docente: gli appelli ad essi collegati (anche di un
        // co-titolare) sono visibili in dettaglio, come i propri.
        $insegnamentiIds = $isAdmin
            ? collect()
            : $user->insegnamenti()->pluck('insegnamenti.id');

        return view('calendario.index', [
            'sessioni' => $sessioni,
            'sessioneSelezionata' => $sessioneSelezionata,
            'perData' => $perData,
            'isAdmin' => $isAdmin,
            'userId' => $user->id,
            'insegnamentiIds' => $insegnamentiIds,
            'idConflitto' => $idConflitto,
        ]);
    }
}
