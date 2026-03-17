<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeceasedRecordResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'full_name' => trim("{$this->first_name} {$this->middle_name} {$this->last_name}"),

            'first_name' => $this->first_name,
            'middle_name' => $this->middle_name,
            'last_name' => $this->last_name,

            'age' => $this->age,

            'birth' => [
                'date' => $this->date_of_birth,
            ],

            'death' => [
                'date' => $this->date_of_death,
                'cause' => $this->cause_of_death,
                'place' => $this->place_of_death,
            ],

            'burial' => [
                'date' => $this->date_of_depository,
            ],
            // these two are not combined bcz some are cremated
            'burial_place' => $this->when(
                $this->corpse_disposal === 'burial',
                $this->burial_place
            ),

            'civil_status' => $this->civil_status,
            'religion' => $this->religion,
            'nationality' => $this->nationality,

            'address' => $this->address,
            'occupation' => [
                'name' => $this->occupation,
                'address' => $this->company_address,
                'supervisor' => $this->company_supervisor_name,
            ],


            'corpse_disposal' => $this->corpse_disposal,

            'cremation' => $this->when(
                $this->corpse_disposal === 'cremation',
                [
                    'place' => $this->cremation_place,
                    'date' => $this->cremation_date,
                ]
            ),

            'family' => [
                'father' => $this->father_name,
                'mother_maiden' => $this->mother_maiden_name,
            ],

            'lgbtq' => $this->part_of_LGBTQ,

            'precinct_num' => $this->precinct_num,

            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
