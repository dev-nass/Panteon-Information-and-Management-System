<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

class ClusterResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $totalLots = DB::table('lots')
            ->where('cluster_id', $this->id)
            ->count();

        $occupiedLots = $this->occupied_lots ?? DB::table('lots')
            ->where('cluster_id', $this->id)
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('burial_records')
                    ->whereColumn('burial_records.lot_id', 'lots.id');
            })
            ->count();

        $status = $occupiedLots === 0 ? 'available' : ($occupiedLots === $totalLots ? 'occupied' : 'partial');

        return [
            'cluster' => [
                'type' => 'Feature',
                'geometry' => is_string($this->coordinates)
                    ? json_decode($this->coordinates, true)
                    : ($this->coordinates ?? ['type' => 'Polygon', 'coordinates' => []]),
                'properties' => [
                    'cluster_id' => $this->id,
                    'phase' => $this->phase->phase_name,
                    'name' => $this->cluster_name,
                    'type' => $this->cluster_type,
                    'status' => $status,
                    'total_lots' => $totalLots,
                    'occupied_lots' => $occupiedLots,
                ],
            ],

            // then fetches the Burial Records alongside it
            'lots' => $this->whenLoaded('lots', function () {
                return LotResource::collection($this->lots);
            }),
        ];
    }
}
