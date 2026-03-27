<?php

namespace App\Http\Controllers\Clerk;

use App\Http\Controllers\Controller;
use App\Http\Resources\PhaseResource;
use App\Models\Phase;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LotManagementController extends Controller
{
    public function index()
    {
        $phases = Phase::with(['clusters.lots'])->get()->map(function ($phase) {
            return [
                'id' => $phase->id,
                'name' => $phase->phase_name,
                'clusters' => $phase->clusters->map(function ($cluster) {
                    return [
                        'id' => $cluster->id,
                        'name' => $cluster->cluster_name,
                        'lots' => $cluster->lots->map(function ($lot) {
                            return [
                                'id' => $lot->id,
                                'number' => $lot->column . '-' . $lot->row,
                                'status' => $lot->burialRecords()->count() > 0 ? 'occupied' : 'available',
                            ];
                        }),
                    ];
                }),
            ];
        });

        return Inertia::render('Clerk/LotManagement/IndexView', [
            'phases' => $phases,
        ]);
    }
}
