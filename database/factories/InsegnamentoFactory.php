<?php

namespace Database\Factories;

use App\Models\CorsoStudio;
use App\Models\Insegnamento;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Insegnamento>
 */
class InsegnamentoFactory extends Factory
{
    protected $model = Insegnamento::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nome' => fake()->randomElement([
                'Programmazione',
                'Basi di Dati',
                'Analisi Matematica',
                'Algoritmi e Strutture Dati',
                'Reti di Calcolatori',
                'Sistemi Operativi',
                'Fisica Generale',
                'Architettura degli Elaboratori',
            ]),
            'anno_frequenza' => fake()->numberBetween(1, 3),
            'corso_studio_id' => CorsoStudio::factory(),
        ];
    }
}
