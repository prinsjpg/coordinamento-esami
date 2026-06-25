<?php

namespace App\Http\Controllers;

use App\Models\Configurazione;
use App\Models\CorsoStudio;
use App\Models\Insegnamento;
use App\Models\Sessione;

class StrutturaController extends Controller
{
    /**
     * Pagina di riepilogo della struttura didattica, con i collegamenti
     * alle singole sezioni di gestione.
     */
    public function index()
    {
        return view('struttura.index', [
            'corsi' => CorsoStudio::count(),
            'insegnamenti' => Insegnamento::count(),
            'sessioni' => Sessione::count(),
            'modalitaConflitto' => Configurazione::query()->value('modalita_conflitto') ?? 'blocco',
        ]);
    }
}
