<?php

namespace App\Models;

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
}
