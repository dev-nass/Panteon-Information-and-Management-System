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

    public function getPhasesWithAvailableLotsForCreate()
    {
        return $this->query('id', 'phase_name')
            ->with([
                'clusters' => function ($query) {
                    $query->select('id', 'phase_id', 'cluster_name', 'cluster_type');
                },
                'clusters.lots' => function ($query) {
                    $query->select('id', 'cluster_id', 'column', 'row');
                },
                'clusters.lots.burialRecords' => function ($query) {
                    $query->select('id', 'lot_id', 'archived_at')->whereNull('archived_at');
                },
            ]);
    }

    /**
     * Description: Retrieves the available lots (no associated burial record) and
     * the lot where the burial record current belongs to
     * */
    public function getPhasesWithAvailableLotsForShow(int $currentBurialRecordId)
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
                            // lot has no active burial record (available)
                            $q->whereDoesntHave('burialRecords', function ($q) {
                                $q->whereNull('archived_at');
                            })
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
