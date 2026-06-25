<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Configurazione extends Model
{
    /** @use HasFactory<\Database\Factories\ConfigurazioneFactory> */
    use HasFactory;

    protected $table = 'configurazione';

    protected $fillable = [
        'modalita_conflitto',
    ];
}
