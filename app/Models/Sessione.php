<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Sessione extends Model
{
    /** @use HasFactory<\Database\Factories\SessioneFactory> */
    use HasFactory;

    protected $table = 'sessioni';

    protected $fillable = [
        'nome',
        'data_inizio',
        'data_fine',
    ];

    protected $casts = [
        'data_inizio' => 'date',
        'data_fine' => 'date',
    ];

    public function periodiInserimento(): HasMany
    {
        return $this->hasMany(PeriodoInserimento::class);
    }

    public function appelli(): HasMany
    {
        return $this->hasMany(Appello::class);
    }

    /**
     * Sessioni la cui finestra di inserimento è aperta oggi (almeno un periodo
     * comprende la data odierna).
     */
    public function scopeConFinestraAperta(Builder $query): Builder
    {
        $oggi = Carbon::today();

        return $query->whereHas('periodiInserimento', fn (Builder $q) => $q
            ->whereDate('data_inizio', '<=', $oggi)
            ->whereDate('data_fine', '>=', $oggi));
    }
}
