<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Appello extends Model
{
    /** @use HasFactory<\Database\Factories\AppelloFactory> */
    use HasFactory;

    protected $table = 'appelli';

    protected $fillable = [
        'insegnamento_id',
        'sessione_id',
        'user_id',
        'data',
        'ora_inizio',
        'ora_fine',
        'aula',
        'note',
    ];

    protected $casts = [
        'data' => 'date',
    ];

    public function insegnamento(): BelongsTo
    {
        return $this->belongsTo(Insegnamento::class);
    }

    public function sessione(): BelongsTo
    {
        return $this->belongsTo(Sessione::class);
    }

    /**
     * Docente che ha creato l'appello.
     */
    public function docente(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Appelli "propri" di un docente: quelli relativi ai suoi insegnamenti
     * (anche se creati da un co-titolare) oppure creati da lui.
     */
    public function scopeVisibiliAlDocente(Builder $query, User $docente): Builder
    {
        return $query->where(function (Builder $q) use ($docente) {
            $q->whereHas('insegnamento.docenti', fn (Builder $d) => $d->whereKey($docente->id))
                ->orWhere('user_id', $docente->id);
        });
    }
}
