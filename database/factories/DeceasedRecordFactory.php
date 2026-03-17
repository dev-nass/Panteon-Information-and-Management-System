<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DeceasedRecord>
 */
class DeceasedRecordFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $dateOfBirth = $this->faker->dateTimeBetween('-90 years', '-1 years');
        $dateOfDeath = $this->faker->dateTimeBetween($dateOfBirth, 'now');

        $corpseDisposal = $this->faker->randomElement([
            'burial',
            'cremation',
            'other',
        ]);

        return [
            'first_name' => $this->faker->firstName,
            'middle_name' => $this->faker->optional()->firstName,
            'last_name' => $this->faker->lastName,

            'age' => Carbon::parse($dateOfBirth)->diffInYears($dateOfDeath),

            'date_of_birth' => $dateOfBirth->format('Y-m-d'),
            'date_of_death' => $dateOfDeath->format('Y-m-d'),

            'cause_of_death' => $this->faker->randomElement([
                'Natural Causes',
                'Heart Attack',
                'Stroke',
                'Accident',
                'Illness',
            ]),

            'place_of_death' => $this->faker->city,

            'civil_status' => $this->faker->randomElement([
                'Single',
                'Married',
                'Widowed',
                'Separated',
            ]),

            'religion' => $this->faker->randomElement([
                'Roman Catholic',
                'Christian',
                'Islam',
                'Others',
            ]),

            'nationality' => 'Filipino',

            'address' => $this->faker->address,
            'occupation' => $this->faker->jobTitle,

            'corpse_disposal' => $corpseDisposal,

            'cremation_place' => $corpseDisposal === 'cremation'
                ? $this->faker->company
                : null,

            'cremation_date' => $corpseDisposal === 'cremation'
                ? $this->faker->date()
                : null,

            'date_of_depository' => $this->faker->optional()->date(),

            'company_address' => $this->faker->optional()->address,
            'company_supervisor_name' => $this->faker->optional()->name,

            'father_name' => $this->faker->name('male'),
            'mother_maiden_name' => $this->faker->lastName,

            'burial_place' => $corpseDisposal === 'burial'
                ? $this->faker->city
                : null,

            'part_of_LGBTQ' => $this->faker->randomElement([
                'yes',
                'no',
                'prefer_not_to_say',
            ]),

            'precinct_num' => $this->faker->numberBetween(1, 9999),
        ];
    }
}
