<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Insegnamento extends Model
{
    /** @use HasFactory<\Database\Factories\InsegnamentoFactory> */
    use HasFactory;

    protected $table = 'insegnamenti';

    protected $fillable = [
        'nome',
        'anno_frequenza',
        'corso_studio_id',
    ];

    protected $casts = [
        'anno_frequenza' => 'integer',
    ];

    public function corsoStudio(): BelongsTo
    {
        return $this->belongsTo(CorsoStudio::class);
    }

    /**
     * Docenti associati a questo insegnamento (pivot docente_insegnamento).
     */
    public function docenti(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'docente_insegnamento')
            ->withTimestamps();
    }

    public function appelli(): HasMany
    {
        return $this->hasMany(Appello::class);
    }
}
