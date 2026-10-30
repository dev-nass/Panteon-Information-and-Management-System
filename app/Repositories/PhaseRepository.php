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

    public function getPhasesWithLotsForCreate()
    {
        return $this->query()
            ->select('id', 'phase_name')
            ->with([
                'clusters' => function ($query) {
                    $query->select('id', 'phase_id', 'cluster_name', 'cluster_type');
                },
                'clusters.lots' => function ($query) {
                    $query->select('id', 'cluster_id', 'column', 'row')
                        ->withCount([
                            'burialRecords as active_burial_count' => fn ($q) => $q->whereNull('archived_at'),
                        ]);
                },
            ]);
    }

    /**
     * Description: Retrieves every lot so the clerk can select any lot,
     * including lots that already have active burial records.
     */
    public function getPhasesWithLotsForShow()
    {
        return $this->query()
            ->select('id', 'phase_name', DB::raw('ST_AsGeoJSON(coordinates) as coordinates'))
            ->with([
                'clusters' => function ($query) {
                    $query->select('id', 'phase_id', 'cluster_name', 'cluster_type', DB::raw('ST_AsGeoJSON(coordinates) as coordinates'));
                },
                'clusters.lots' => function ($query) {
                    $query->select('id', 'cluster_id', DB::raw('`column`'), DB::raw('`row`'), DB::raw('ST_AsGeoJSON(coordinates) as coordinates'));
                },
            ])
            ->get();
    }
}
