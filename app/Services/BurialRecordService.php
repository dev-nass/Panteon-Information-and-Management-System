<?php

namespace app\Services;

use App\Repositories\ApplicantRepository;
use App\Repositories\DeceasedRecordRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class BurialRecordService
{
    public function __construct(
        protected DeceasedRecordRepository $deceased_repo,
        protected ApplicantRepository $applicant_repo
    ) {}

    public function store(array $applicantData, array $deceasedData): Model
    {
        return DB::transaction(function () use ($applicantData, $deceasedData) {

            $applicant = $this->applicant_repo->findOrCreateApplicant($applicantData);

            return $this->deceased_repo->createDeceasedRecord(
                $deceasedData,
                $applicant->id
            );
        });
    }
}
