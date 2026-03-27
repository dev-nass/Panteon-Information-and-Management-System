<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Phase>
 */
class PhaseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'phase_code' => $this->faker->unique()->numberBetween(100, 999),
            'phase_name' => $this->faker->word(),
            'description' => $this->faker->sentence(),
            'total_capacity' => $this->faker->numberBetween(50, 200),
            'status' => 'active',
        ];
    }
}
