<?php

namespace App\Repositories;

use App\Models\Phase;
use Illuminate\Support\Facades\DB;

class PhaseRepository extends Repository
{
    public function __construct(Phase $model)
    {
        return parent::__construct($model);
    }

    public function getPhasesWithAvailableLots(int $currentBurialRecordId)
    {
        return $this->query()
            ->select('id', 'phase_name', DB::raw('ST_AsGeoJSON(coordinates) as coordinates'))
            ->with([
                'clusters' => function ($query) {
                    $query->select('id', 'phase_id', 'cluster_name', 'cluster_type', DB::raw('ST_AsGeoJSON(coordinates) as coordinates'));
                },
                'clusters.lots' => function ($query) use ($currentBurialRecordId) {
                    $query->select('id', 'cluster_id', DB::raw('`column`'), DB::raw('`row`'), DB::raw('ST_AsGeoJSON(coordinates) as coordinates'))
                        ->where(function ($q) use ($currentBurialRecordId) {
                            // lot has no burial record (available)
                            $q->doesntHave('burialRecords')
                            // OR lot belongs to the current burial record
                                ->orWhereHas('burialRecords', function ($q) use ($currentBurialRecordId) {
                                    $q->where('id', $currentBurialRecordId);
                                });
                        });
                },
            ])
            ->get();
    }
}
