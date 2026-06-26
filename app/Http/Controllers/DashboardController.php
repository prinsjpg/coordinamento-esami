<?php

namespace App\Http\Controllers;

use App\Models\Appello;
use App\Models\CorsoStudio;
use App\Models\Insegnamento;
use App\Models\Sessione;
use App\Services\MonitoraggioService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    /**
     * Mostra la dashboard, con contenuti differenziati in base al ruolo.
     */
    public function index(Request $request, MonitoraggioService $monitoraggio)
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

            $prossimiAppelli = Appello::with(['insegnamento', 'docente'])
                ->whereDate('data', '>=', Carbon::today())
                ->orderBy('data')
                ->orderBy('ora_inizio')
                ->take(5)
                ->get();

            return view('dashboard', [
                'ruolo' => 'amministratore',
                'stats' => $stats,
                'prossimiAppelli' => $prossimiAppelli,
                'segnalazioni' => $monitoraggio->segnalazioniAdmin(),
            ]);
        }

        // Dati per il docente: i propri insegnamenti e gli appelli ad essi
        // collegati (anche se inseriti da un co-titolare) oltre ai propri.
        $insegnamenti = $user->insegnamenti()->with('corsoStudio')->get();

        $mieiAppelli = Appello::with('insegnamento')
            ->visibiliAlDocente($user)
            ->whereDate('data', '>=', Carbon::today())
            ->orderBy('data')
            ->orderBy('ora_inizio')
            ->take(5)
            ->get();

        return view('dashboard', [
            'ruolo' => 'docente',
            'insegnamenti' => $insegnamenti,
            'mieiAppelli' => $mieiAppelli,
            'daCompletare' => $monitoraggio->segnalazioniDocente($user),
        ]);
    }
}
