<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LotResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $burialRecordsCount = $this->whenLoaded('burialRecords', function () {
            return $this->burialRecords->count();
        }, 0);

        $status = $burialRecordsCount > 0 ? 'occupied' : 'available';

        return [
            'lot' => [
                'type' => 'Feature',
                'geometry' => is_string($this->coordinates)
                    ? json_decode($this->coordinates, true)
                    : ($this->coordinates ?? ['type' => 'Polygon', 'coordinates' => []]),
                'properties' => [
                    'phase' => $this->cluster->phase->phase_name ?? null,
                    'cluster' => $this->cluster->cluster_name ?? null,
                    'lot_id' => $this->id,
                    'column' => $this->column,
                    'row' => $this->row,
                    'status' => $status,
                    'burial_count' => $burialRecordsCount,
                ],
            ],

            'burial_records' => $this->whenLoaded('burialRecords', function () {
                return BurialRecordResource::collection($this->burialRecords);
            }),
        ];
    }
}
