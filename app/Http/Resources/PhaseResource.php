<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

class PhaseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $totalCapacity = DB::table('lots')
            ->join('clusters', 'lots.cluster_id', '=', 'clusters.id')
            ->where('clusters.phase_id', $this->id)
            ->count();

        $occupants = DB::table('burial_records')
            ->join('lots', 'burial_records.lot_id', '=', 'lots.id')
            ->join('clusters', 'lots.cluster_id', '=', 'clusters.id')
            ->where('clusters.phase_id', $this->id)
            ->count();

        $totalClusters = DB::table('clusters')
            ->where('phase_id', $this->id)
            ->count();

        return [
            'id' => $this->id,
            'phase' => [
                'type' => 'Feature',
                'geometry' => is_string($this->coordinates)
                    ? json_decode($this->coordinates, true)
                    : ($this->coordinates ?? ['type' => 'Polygon', 'coordinates' => []]),
                'properties' => [
                    // 'phase_id' => $this->id,
                    // 'phase_code' => $this->phase_code,
                    'phase_name' => $this->phase_name,
                    // 'description' => $this->description,
                    // 'status' => $this->status,
                    'total_capacity' => $totalCapacity,
                    'occupants' => $occupants,
                    'total_clusters' => $totalClusters,
                ],
            ],
        ];
    }
}