<?php

namespace App\Repositories;

use App\Models\DeceasedRecord;
use Illuminate\Database\Eloquent\Model;

class DeceasedRecordRepository extends Repository
{
    public function __construct(DeceasedRecord $model)
    {
        return parent::__construct($model);
    }

    public function createDeceasedRecord(array $validated, int $applicantId): Model
    {
        return $this->create([
            'applicant_id' => $applicantId,
            'first_name' => $validated['first_name'],
            'middle_name' => $validated['middle_name'] ?? null,
            'last_name' => $validated['last_name'],
            'age' => $validated['age'] ?? null,
            'date_of_birth' => $validated['birth_date'] ?? null,
            'date_of_death' => $validated['death_date'] ?? null,
            'cause_of_death' => $validated['death_cause'] ?? null,
            'place_of_death' => $validated['death_place'] ?? null,
            'civil_status' => $validated['civil_status'] ?? null,
            'religion' => $validated['religion'] ?? null,
            'nationality' => $validated['nationality'] ?? null,
            'address' => $validated['address'] ?? null,
            'occupation' => $validated['occupation_name'] ?? null,
            'corpse_disposal' => $validated['corpse_disposal'] ?? null,
            'cremation_place' => $validated['cremation_place'] ?? null,
            'cremation_date' => $validated['cremation_date'] ?? null,
            'date_of_depository' => $validated['burial_date'] ?? null,
            'company_address' => $validated['company_address'] ?? null,
            'company_supervisor_name' => $validated['company_supervisor'] ?? null,
            'father_name' => $validated['father_name'] ?? null,
            'mother_maiden_name' => $validated['mother_maiden_name'] ?? null,
            'burial_place' => $validated['burial_place'] ?? null,
            'part_of_LGBTQ' => $validated['lgbtq'] ?? null,
            'precinct_num' => $validated['precinct_num'] ?? null,
        ]);
    }

    public function updateDeceasedRecord(Model $deceased, array $data): bool
    {
        return $this->update($deceased, [
            'first_name' => $data['first_name'],
            'middle_name' => $data['middle_name'] ?? null,
            'last_name' => $data['last_name'],
            'age' => $data['age'] ?? null,
            'date_of_birth' => $data['birth']['date'] ?? null,
            'civil_status' => $data['civil_status'] ?? null,
            'religion' => $data['religion'] ?? null,
            'nationality' => $data['nationality'] ?? null,
            'occupation' => $data['occupation']['name'] ?? null,
            'address' => $data['address'] ?? null,
            'part_of_LGBTQ' => $data['lgbtq'] ?? null,
            'precinct_num' => $data['precinct_num'] ?? null,
            'date_of_death' => $data['death']['date'] ?? null,
            'cause_of_death' => $data['death']['cause'] ?? null,
            'place_of_death' => $data['death']['place'] ?? null,
            'corpse_disposal' => $data['corpse_disposal'] ?? null,
            'cremation_place' => $data['cremation']['place'] ?? null,
            'cremation_date' => $data['cremation']['date'] ?? null,
            'burial_place' => $data['burial_place'] ?? null,
            'date_of_depository' => $data['burial']['date'] ?? null,
            'father_name' => $data['family']['father'] ?? null,
            'mother_maiden_name' => $data['family']['mother_maiden'] ?? null,
            'company_address' => $data['occupation']['address'] ?? null,
            'company_supervisor_name' => $data['occupation']['supervisor'] ?? null,
        ]);
    }
}
