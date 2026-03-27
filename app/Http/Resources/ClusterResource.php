<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClusterResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $totalLots = $this->whenLoaded('lots', function () {
            return $this->lots->count();
        }, 0);

        $occupiedLots = $this->whenLoaded('lots', function () {
            return $this->lots->filter(function ($lot) {
                return $lot->relationLoaded('burialRecords') && $lot->burialRecords->count() > 0;
            })->count();
        }, 0);

        $status = $occupiedLots === 0 ? 'available' : ($occupiedLots === $totalLots ? 'occupied' : 'partial');

        return [
            'cluster' => [
                'type' => 'Feature',
                'geometry' => is_string($this->coordinates)
                    ? json_decode($this->coordinates, true)
                    : ($this->coordinates ?? ['type' => 'Polygon', 'coordinates' => []]),
                'properties' => [
                    'cluster_id' => $this->id,
                    'name' => $this->cluster_name,
                    'type' => $this->cluster_type,
                    'status' => $status,
                    'total_lots' => $totalLots,
                    'occupied_lots' => $occupiedLots,
                ],
            ],

            'lots' => $this->whenLoaded('lots', function () {
                return LotResource::collection($this->lots);
            }),
        ];
    }
}
