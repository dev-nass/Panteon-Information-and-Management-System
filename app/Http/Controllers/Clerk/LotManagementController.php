<?php

namespace App\Http\Controllers\Clerk;

use App\Http\Controllers\Controller;
use App\Models\Lot;
use App\Models\Phase;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class LotManagementController extends Controller
{
    public function index()
    {
        $phases = Phase::select('id', 'phase_name', DB::raw('ST_AsGeoJSON(coordinates) as coordinates'))
            ->withCount('clusters')
            ->get()
            ->map(function ($phase) {
                return [
                    'id' => $phase->id,
                    'name' => $phase->phase_name,
                    'total_clusters' => $phase->clusters_count,
                    'coordinates' => $phase->coordinates,
                    'isPhase_mapped' => !is_null($phase->coordinates),
                ];
            });

        return Inertia::render('Shared/LotManagement/IndexView', [
            'phases' => $phases,
        ]);
    }


    /**
     * Description: Redirect to Burial Record Show by finding burial from lot
     */
    public function show(Lot $lot)
    {
        $burialRecord = $lot->burialRecords()->first();

        if (!$burialRecord) {
            return to_route('admin.lot_management.index')
                ->with('error', 'No burial record found for this lot.');
        }

        return to_route('clerk.burial_records.show', $burialRecord->id);
    }

}
