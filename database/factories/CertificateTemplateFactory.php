<?php

namespace Database\Factories;

use App\Models\CertificateTemplate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CertificateTemplate>
 */
class CertificateTemplateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'file_path' => 'certificate_templates/'.fake()->uuid().'.pdf',
            'fields' => null,
            'uploaded_by' => User::factory(),
        ];
    }
}
