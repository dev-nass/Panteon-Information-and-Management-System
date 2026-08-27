<?php

namespace App\Repositories;

use App\Models\DeceasedRecord;
use App\Services\RecordNormalizationService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class DeceasedRecordRepository extends Repository
{
    public function __construct(
        DeceasedRecord $model,
        protected RecordNormalizationService $normalizer
    ) {
        parent::__construct($model);
    }

    public function createDeceasedRecord(array $validated, int $applicantId): Model
    {
        $birthDate = $validated['birth_date'] ?? null;
        $deathDate = $validated['death_date'] ?? null;
        $explicitAge = $validated['age'] ?? null;

        $duplicate = $this->normalizer->findDuplicateDeceased(
            $validated['first_name'],
            $validated['last_name'],
            $birthDate,
            $deathDate
        );

        if ($duplicate) {
            throw ValidationException::withMessages([
                'first_name' => "A record for {$validated['first_name']} {$validated['last_name']} already exists (ID: {$duplicate->id}).",
            ]);
        }

        return $this->create([
            'applicant_id' => $applicantId,
            'first_name' => $validated['first_name'],
            'middle_name' => $validated['middle_name'] ?? null,
            'last_name' => $validated['last_name'],
            'age' => $this->normalizer->computeAge($birthDate, $deathDate, $explicitAge),
            'date_of_birth' => $birthDate,
            'date_of_death' => $deathDate,
            'cause_of_death' => $validated['death_cause'] ?? null,
            'place_of_death' => $validated['death_place'] ?? null,
            'civil_status' => $validated['civil_status'] ?? null,
            'religion' => $validated['religion'] ?? null,
            'nationality' => $validated['nationality'] ?? null,
            'address' => $this->normalizer->normalizeAddress($validated['address'] ?? null),
            'occupation' => $validated['occupation_name'] ?? null,
            'corpse_disposal' => $validated['corpse_disposal'] ?? null,
            'cremation_place' => $validated['cremation_place'] ?? null,
            'cremation_date' => $validated['cremation_date'] ?? null,
            'date_of_depository' => $validated['burial_date'] ?? null,
            'time_of_depository' => $validated['burial_time'] ?? null,
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
        $birthDate = $data['birth']['date'] ?? null;
        $deathDate = $data['death']['date'] ?? null;
        $explicitAge = $data['age'] ?? null;

        return $this->update($deceased, [
            'first_name' => $data['first_name'],
            'middle_name' => $data['middle_name'] ?? null,
            'last_name' => $data['last_name'],
            'age' => $this->normalizer->computeAge($birthDate, $deathDate, $explicitAge),
            'date_of_birth' => $birthDate,
            'civil_status' => $data['civil_status'] ?? null,
            'religion' => $data['religion'] ?? null,
            'nationality' => $data['nationality'] ?? null,
            'occupation' => $data['occupation']['name'] ?? null,
            'address' => $this->normalizer->normalizeAddress($data['address'] ?? null),
            'part_of_LGBTQ' => $data['lgbtq'] ?? null,
            'precinct_num' => $data['precinct_num'] ?? null,
            'date_of_death' => $deathDate,
            'cause_of_death' => $data['death']['cause'] ?? null,
            'place_of_death' => $data['death']['place'] ?? null,
            'corpse_disposal' => $data['corpse_disposal'] ?? null,
            'cremation_place' => $data['cremation']['place'] ?? null,
            'cremation_date' => $data['cremation']['date'] ?? null,
            'burial_place' => $data['burial_place'] ?? null,
            'date_of_depository' => $data['burial']['date'] ?? null,
            'time_of_depository' => $data['burial']['time'] ?? null,
            'father_name' => $data['family']['father'] ?? null,
            'mother_maiden_name' => $data['family']['mother_maiden'] ?? null,
            'company_address' => $data['occupation']['address'] ?? null,
            'company_supervisor_name' => $data['occupation']['supervisor'] ?? null,
        ]);
    }
}
