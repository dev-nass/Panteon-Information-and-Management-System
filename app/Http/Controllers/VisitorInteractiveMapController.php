<?php

namespace App\Http\Controllers;

use App\Models\BurialRecord;
use App\Models\Cluster;
use App\Http\Resources\ClusterResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class VisitorInteractiveMapController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search', '');

        $burialRecords = BurialRecord::with('deceasedRecord')
            ->when($search, function ($query, $search) {
                $query->whereHas('deceasedRecord', function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                });
            })
            ->limit(10)
            ->get();

        return Inertia::render('VisitorInteractiveMapView', [
            'burial_records' => $burialRecords,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function search(Request $request)
    {
        $firstName = $request->input('firstName', '');
        $lastName = $request->input('lastName', '');
        $burialDate = $request->input('burialDate', '');

        $lotIds = DB::table('burial_records')
            ->join('deceased_records', 'burial_records.deceased_record_id', '=', 'deceased_records.id')
            ->when($firstName, function ($query) use ($firstName) {
                $query->where('deceased_records.first_name', 'like', "%{$firstName}%");
            })
            ->when($lastName, function ($query) use ($lastName) {
                $query->where('deceased_records.last_name', 'like', "%{$lastName}%");
            })
            ->when($burialDate, function ($query) use ($burialDate) {
                $query->where('deceased_records.date_of_depository', 'like', "%{$burialDate}%");
            })
            ->distinct()
            ->limit(10)
            ->pluck('burial_records.lot_id');

        $clusterIds = DB::table('lots')
            ->whereIn('id', $lotIds)
            ->distinct()
            ->pluck('cluster_id');

        $clusters = DB::table('clusters')
            ->whereIn('id', $clusterIds)
            ->select('id', 'cluster_name', 'cluster_type', DB::raw('ST_AsGeoJSON(coordinates) as coordinates'))
            ->get()
            ->map(function ($cluster) use ($lotIds) {
                $clusterModel = Cluster::find($cluster->id);
                $clusterModel->coordinates = $cluster->coordinates;
                $clusterModel->load([
                    'lots' => function ($query) use ($lotIds) {
                        $query->select('id', 'cluster_id', DB::raw('`column`'), DB::raw('`row`'), DB::raw('ST_AsGeoJSON(coordinates) as coordinates'))
                            ->whereIn('id', $lotIds);
                    },
                    'lots.burialRecords.deceasedRecord',
                    'lots.burialRecords.user'
                ]);
                return $clusterModel;
            });

        return ClusterResource::collection($clusters);
    }
}
