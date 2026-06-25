<?php

namespace Database\Factories;

use App\Models\Configurazione;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Configurazione>
 */
class ConfigurazioneFactory extends Factory
{
    protected $model = Configurazione::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'modalita_conflitto' => 'blocco',
        ];
    }
}
