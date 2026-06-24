<?php

namespace Database\Factories;

use App\Models\Appello;
use App\Models\Insegnamento;
use App\Models\Sessione;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Appello>
 */
class AppelloFactory extends Factory
{
    protected $model = Appello::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $oraInizio = fake()->numberBetween(9, 15);

        return [
            'insegnamento_id' => Insegnamento::factory(),
            'sessione_id' => Sessione::factory(),
            'user_id' => User::factory(),
            'data' => fake()->dateTimeBetween('now', '+2 months')->format('Y-m-d'),
            'ora_inizio' => sprintf('%02d:00', $oraInizio),
            'ora_fine' => sprintf('%02d:00', $oraInizio + 2),
            'aula' => 'Aula ' . fake()->randomElement(['A1', 'A2', 'B1', 'B2', 'Magna']),
            'note' => fake()->optional()->sentence(),
        ];
    }
}
