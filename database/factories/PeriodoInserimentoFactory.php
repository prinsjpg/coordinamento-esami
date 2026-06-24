<?php

namespace Database\Factories;

use App\Models\PeriodoInserimento;
use App\Models\Sessione;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PeriodoInserimento>
 */
class PeriodoInserimentoFactory extends Factory
{
    protected $model = PeriodoInserimento::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $inizio = fake()->dateTimeBetween('-10 days', '+10 days');
        $fine = (clone $inizio)->modify('+15 days');

        return [
            'sessione_id' => Sessione::factory(),
            'data_inizio' => $inizio,
            'data_fine' => $fine,
        ];
    }
}
