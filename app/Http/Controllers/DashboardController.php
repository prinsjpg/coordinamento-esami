<?php

namespace App\Http\Controllers;

use App\Models\Appello;
use App\Models\CorsoStudio;
use App\Models\Insegnamento;
use App\Models\Sessione;
use App\Services\ConflittoService;
use App\Services\MonitoraggioService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    /**
     * Mostra la dashboard, con contenuti differenziati in base al ruolo.
     */
    public function index(Request $request, MonitoraggioService $monitoraggio, ConflittoService $conflitti)
    {
        $user = $request->user();

        // Dati per l'amministratore: visione complessiva della struttura
        if ($user->hasRole('amministratore')) {
            $stats = [
                'corsi' => CorsoStudio::count(),
                'insegnamenti' => Insegnamento::count(),
                'sessioni' => Sessione::count(),
                'appelli' => Appello::count(),
            ];

            // Appelli futuri: base per i prossimi appelli e per la rilevazione
            // dei conflitti (che riguardano sempre appelli nella stessa data).
            $appelliFuturi = Appello::with(['insegnamento.corsoStudio', 'docente'])
                ->whereDate('data', '>=', Carbon::today())
                ->orderBy('data')
                ->orderBy('ora_inizio')
                ->get();

            $idConflitto = $conflitti->idInConflitto($appelliFuturi);

            return view('dashboard', [
                'ruolo' => 'amministratore',
                'stats' => $stats,
                'prossimiAppelli' => $appelliFuturi->take(5),
                'segnalazioni' => $monitoraggio->segnalazioniAdmin(),
                'appelliInConflitto' => $appelliFuturi->whereIn('id', $idConflitto)->values(),
                'idConflitto' => $idConflitto,
            ]);
        }

        // Dati per il docente: i propri insegnamenti e gli appelli ad essi
        // collegati (anche se inseriti da un co-titolare) oltre ai propri.
        $insegnamenti = $user->insegnamenti()->with('corsoStudio')->get();

        $mieiAppelli = Appello::with('insegnamento.corsoStudio')
            ->visibiliAlDocente($user)
            ->whereDate('data', '>=', Carbon::today())
            ->orderBy('data')
            ->orderBy('ora_inizio')
            ->take(5)
            ->get();

        // I conflitti vanno cercati contro tutti gli appelli futuri, non solo i
        // propri: un appello può confliggere con quello di un altro docente.
        $universo = Appello::with('insegnamento')
            ->whereDate('data', '>=', Carbon::today())
            ->get();

        return view('dashboard', [
            'ruolo' => 'docente',
            'insegnamenti' => $insegnamenti,
            'mieiAppelli' => $mieiAppelli,
            'daCompletare' => $monitoraggio->segnalazioniDocente($user),
            'idConflitto' => $conflitti->idInConflitto($mieiAppelli, $universo),
        ]);
    }
}
