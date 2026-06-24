<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PeriodoInserimento extends Model
{
    /** @use HasFactory<\Database\Factories\PeriodoInserimentoFactory> */
    use HasFactory;

    protected $table = 'periodi_inserimento';

    protected $fillable = [
        'sessione_id',
        'data_inizio',
        'data_fine',
    ];

    protected $casts = [
        'data_inizio' => 'date',
        'data_fine' => 'date',
    ];

    public function sessione(): BelongsTo
    {
        return $this->belongsTo(Sessione::class);
    }
}
