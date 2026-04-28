<?php

namespace App\Services;

use App\Repositories\ApplicantRepository;
use App\Repositories\BurialRecordRepository;
use App\Repositories\DeceasedRecordRepository;
use App\Repositories\PhaseRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class BurialRecordService
{
    public function __construct(
        protected DeceasedRecordRepository $deceased_repo,
        protected ApplicantRepository $applicant_repo,
        protected BurialRecordRepository $burial_repo,
        protected PhaseRepository $phase_repo,
    ) {
    }

    /**
     * @param  array  $applicantData  validated data of applicant,
     * @param  array  $deceasedData  validated data of deceased,
     * @param  int  $createdBy  user ID of the login clerk
     * */
    public function store(array $applicantData, array $deceasedData, array $lotData, int $createdBy): Model
    {
        return DB::transaction(function () use ($applicantData, $createdBy, $deceasedData, $lotData) {

            $applicant = $this->applicant_repo->findOrCreateApplicant($applicantData);

            $deceased = $this->deceased_repo->createDeceasedRecord($deceasedData, $applicant->id);

            return $this->burial_repo->createBurialRecord(
                $deceased->id,
                $lotData['lot_id'],
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
                    $burialRecord,
                    $deceasedData['lot_id'],
                    $updatedBy
                );
            }

        });

    }

    public function getCreateData()
    {
        return $this->phase_repo->getPhasesWithAvailableLotsForCreate()
            ->with('clusters.lots.burialRecords')
            ->get()
            ->map(function ($phase) {
                return [
                    'id' => $phase->id,
                    'name' => $phase->phase_name,
                    'clusters' => $phase->clusters->map(function ($cluster) {
                        return [
                            'id' => $cluster->id,
                            'name' => $cluster->cluster_name,
                            'cluster_type' => $cluster->cluster_type,
                            'lots' => $cluster->lots->map(function ($lot) {
                                return [
                                    'id' => $lot->id,
                                    'column' => $lot->column,
                                    'row' => $lot->row,
                                    'is_occupied' => $lot->burialRecords->isNotEmpty(),
                                ];
                            }),
                        ];
                    }),
                ];
            });

    }

    /**
     * Description: For the showing and editing of burial record
     * */
    public function getShowData(Model $burialRecord): array
    {
        $burialRecord->load([
            'deceasedRecord',
            'deceasedRecord.applicant',
            'lot' => function ($query) {
                $query->select('id', 'cluster_id', 'column', 'row', DB::raw('ST_AsGeoJSON(coordinates) as coordinates'));
            },
            'lot.cluster' => function ($query) {
                $query->select('id', 'phase_id', 'cluster_name', 'cluster_type', DB::raw('ST_AsGeoJSON(coordinates) as coordinates'));
            },

            'lot.cluster.phase:id,phase_name',
            'user:id,first_name,middle_name,last_name,role',
        ]);

        $lot = $burialRecord->lot;

        return [
            'burial_record' => $burialRecord,
            'current_selection' => $lot ? [
                'lot_id' => $lot->id,
                'cluster_id' => $lot->cluster_id,
                'phase_id' => $lot->cluster->phase_id,
            ] : null,

            'phases' => $this->phase_repo
                ->getPhasesWithAvailableLotsForShow($burialRecord->id)
                ->map(function ($phase) {
                    return [
                        'id' => $phase->id,
                        'name' => $phase->phase_name,
                        'coordinates' => $phase->coordinates,
                        'clusters' => $phase->clusters->map(function ($cluster) {
                            return [
                                'id' => $cluster->id,
                                'name' => $cluster->cluster_name,
                                'cluster_type' => $cluster->cluster_type,
                                'coordinates' => $cluster->coordinates,
                                'lots' => $cluster->lots->map(function ($lot) {
                                    return [
                                        'id' => $lot->id,
                                        'column' => $lot->column,
                                        'row' => $lot->row,
                                        'coordinates' => $lot->coordinates,
                                    ];
                                }),
                            ];
                        }),
                    ];
                }),
        ];
    }
}
