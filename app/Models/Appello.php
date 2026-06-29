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
     * Appelli "propri" di un docente: quelli relativi agli insegnamenti di cui è
     * titolare (anche se creati da un co-titolare). La proprietà segue la
     * titolarità dell'insegnamento, non chi ha materialmente creato l'appello:
     * se un docente viene rimosso dall'incarico, smette di vederli e gestirli.
     */
    public function scopeVisibiliAlDocente(Builder $query, User $docente): Builder
    {
        return $query->whereHas('insegnamento.docenti', fn (Builder $d) => $d->whereKey($docente->id));
    }
}
