<?php

namespace App\Repositories;

use App\Models\Applicant;
use Illuminate\Database\Eloquent\Model;

class ApplicantRepository extends Repository
{
    public function __construct(Applicant $model)
    {
        return parent::__construct($model);
    }

    public function findOrCreateApplicant(array $validated): Model
    {
        $contactNumber = $validated['applicant_contact_number'] ?? null;

        if ($contactNumber !== null && $contactNumber !== '') {
            $existing = $this->findByContactNumber($contactNumber);

            if ($existing !== null) {
                return $existing;
            }
        }

        return $this->create([
            'first_name' => $validated['applicant_first_name'],
            'middle_name' => $validated['applicant_middle_name'] ?? null,
            'last_name' => $validated['applicant_last_name'],
            'contact_number' => $contactNumber,
            'relationship' => $validated['applicant_relationship'] ?? null,
        ]);
    }

    /**
     * Description: Custom method for finding the applicant record based
     * on contact number
     * */
    public function findByContactNumber(?string $contactNumber, array $columns = ['*'], array|string $relations = []): ?Model
    {
        if ($contactNumber === null || $contactNumber === '') {
            return null;
        }

        return $this->query()
            ->with($relations)
            ->where('contact_number', $contactNumber)
            ->first($columns);
    }

    public function updateApplicant(Model $applicant, array $data): bool
    {
        return $this->update($applicant, [
            'first_name' => $data['first_name'],
            'middle_name' => $data['middle_name'] ?? null,
            'last_name' => $data['last_name'],
            'contact_number' => $data['contact_number'],
            'relationship' => $data['relationship'] ?? null,
        ]);
    }
}
