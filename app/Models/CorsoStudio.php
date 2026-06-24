<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
}
