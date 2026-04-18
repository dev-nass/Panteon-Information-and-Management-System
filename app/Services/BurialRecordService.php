<?php

namespace App\Services;

use App\Repositories\ApplicantRepository;
use App\Repositories\BurialRecordRepository;
use App\Repositories\DeceasedRecordRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class BurialRecordService
{
    public function __construct(
        protected DeceasedRecordRepository $deceased_repo,
        protected ApplicantRepository $applicant_repo,
        protected BurialRecordRepository $burial_repo,
    ) {}

    /**
     * @param  array  $applicantData  validated data of applicant,
     * @param  array  $deceasedData  validated data of deceased,
     * @param  int  $createdBy  user ID of the login clerk
     * */
    public function store(array $applicantData, array $deceasedData, int $createdBy): Model
    {
        return DB::transaction(function () use ($applicantData, $createdBy, $deceasedData) {

            $applicant = $this->applicant_repo->findOrCreateApplicant($applicantData);

            $deceased = $this->deceased_repo->createDeceasedRecord($deceasedData, $applicant->id);

            return $this->burial_repo->createBurialRecord(
                $deceased->id,
                $applicant->id,
                $createdBy
            );
        });
    }

    /**
     * @param  Model  $burialRecord  model instance of the burial record,
     * @param  array  $deceasedData  validated data of deceased
     * @param  int  $updatedBy  user id of the login clerk
     * */
    public function update(Model $burialRecord, array $deceasedData, int $updatedBy)
    {
        return DB::transaction(function () use ($burialRecord, $deceasedData, $updatedBy) {
            $this->deceased_repo->updateDeceasedRecord(
                $burialRecord->deceasedRecord,
                $deceasedData['deceased']
            );

            $applicant = $burialRecord->deceasedRecord->applicant;
            if ($applicant) {
                $this->applicant_repo->updateApplicant(
                    $applicant,
                    $deceasedData['deceased']['applicant']
                );
            }

            if (isset($deceasedData['lot_id'])) {
                $this->burial_repo->updateBurialRecord(
                    $burialRecord, $deceasedData['lot_id'], $updatedBy
                );
            }

        });

    }
}
