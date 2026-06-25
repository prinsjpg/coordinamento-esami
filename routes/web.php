<?php

use App\Http\Controllers\AppelloController;
use App\Http\Controllers\CalendarioController;
use App\Http\Controllers\ConfigurazioneController;
use App\Http\Controllers\CorsoStudioController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\InsegnamentoController;
use App\Http\Controllers\PeriodoInserimentoController;
use App\Http\Controllers\SessioneController;
use App\Http\Controllers\StrutturaController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Gestione della struttura didattica: riservata all'amministratore
    Route::middleware('role:amministratore')->group(function () {
        Route::get('/struttura', [StrutturaController::class, 'index'])->name('struttura.index');

        Route::resource('corsi', CorsoStudioController::class)
            ->parameters(['corsi' => 'corso'])
            ->except('show');

        Route::resource('insegnamenti', InsegnamentoController::class)
            ->parameters(['insegnamenti' => 'insegnamento'])
            ->except('show');

        Route::resource('sessioni', SessioneController::class)
            ->parameters(['sessioni' => 'sessione']);

        // Periodi di inserimento, annidati nella sessione
        Route::post('/sessioni/{sessione}/periodi', [PeriodoInserimentoController::class, 'store'])
            ->name('periodi.store');
        Route::delete('/sessioni/{sessione}/periodi/{periodo}', [PeriodoInserimentoController::class, 'destroy'])
            ->name('periodi.destroy');

        // Import CSV della struttura didattica
        Route::get('/import', [ImportController::class, 'index'])->name('import.index');
        Route::get('/import/template', [ImportController::class, 'template'])->name('import.template');
        Route::post('/import', [ImportController::class, 'store'])->name('import.store');

        // Configurazione dei conflitti (riga unica)
        Route::get('/configurazione', [ConfigurazioneController::class, 'edit'])->name('configurazione.edit');
        Route::put('/configurazione', [ConfigurazioneController::class, 'update'])->name('configurazione.update');
    });

    // Gestione degli appelli: docenti (sui propri) e amministratore (su tutti)
    Route::middleware('role:docente|amministratore')->group(function () {
        // Verifica conflitti in tempo reale (AJAX)
        Route::get('/appelli/verifica-conflitto', [AppelloController::class, 'verificaConflitto'])
            ->name('appelli.verifica-conflitto');

        Route::resource('appelli', AppelloController::class)
            ->parameters(['appelli' => 'appello'])
            ->except('show');

        // Calendario degli appelli per sessione (visibilità per ruolo)
        Route::get('/calendario', [CalendarioController::class, 'index'])->name('calendario.index');
    });
});
