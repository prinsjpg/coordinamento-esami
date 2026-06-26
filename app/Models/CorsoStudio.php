<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class CorsoStudio extends Model
{
    /** @use HasFactory<\Database\Factories\CorsoStudioFactory> */
    use HasFactory;

    protected $table = 'corsi_studio';

    protected $fillable = [
        'nome',
    ];

    public function insegnamenti(): HasMany
    {
        return $this->hasMany(Insegnamento::class);
    }

    /**
     * Appelli del corso, attraverso i suoi insegnamenti (utile per quantificare
     * l'impatto di una cancellazione a cascata).
     */
    public function appelli(): HasManyThrough
    {
        return $this->hasManyThrough(Appello::class, Insegnamento::class);
    }
}
