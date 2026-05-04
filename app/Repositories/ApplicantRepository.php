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
        return $this->findByContactNumber($validated['applicant_contact_number'])
            ?? $this->create([
                'first_name' => $validated['applicant_first_name'],
                'middle_name' => $validated['applicant_middle_name'] ?? null,
                'last_name' => $validated['applicant_last_name'],
                'contact_number' => $validated['applicant_contact_number'],
            ]);
    }

    /**
     * Description: Custom method for finding the applicant record based
     * on contact number
     * */
    public function findByContactNumber(int $contactNumber, array $columns = ['*'], array|string $relations = []): ?Model
    {
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
        ]);
    }
}
