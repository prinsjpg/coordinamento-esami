<?php

namespace Database\Factories;

use App\Models\Sessione;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sessione>
 */
class SessioneFactory extends Factory
{
    protected $model = Sessione::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $inizio = fake()->dateTimeBetween('now', '+2 months');
        $fine = (clone $inizio)->modify('+1 month');

        return [
            'nome' => fake()->randomElement(['Sessione Invernale', 'Sessione Estiva', 'Sessione Autunnale']),
            'data_inizio' => $inizio,
            'data_fine' => $fine,
        ];
    }
}
