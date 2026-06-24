<?php

namespace Database\Factories;

use App\Models\CorsoStudio;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CorsoStudio>
 */
class CorsoStudioFactory extends Factory
{
    protected $model = CorsoStudio::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nome' => 'Corso di Laurea in ' . fake()->unique()->randomElement([
                'Informatica',
                'Ingegneria Informatica',
                'Matematica',
                'Fisica',
                'Economia',
                'Statistica',
            ]),
        ];
    }
}
