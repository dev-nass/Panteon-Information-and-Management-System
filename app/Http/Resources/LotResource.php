<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LotResource extends JsonResource
{
    /**
     * Description: used for Interactive Map and Api/MapDataController or the actual Map
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            // Lot geometry
            'lot' => [
                'type' => 'Feature',
                'geometry' => is_string($this->coordinates) 
                    ? json_decode($this->coordinates, true) 
                    : ($this->coordinates ?? ['type' => 'Point', 'coordinates' => []]),
                'properties' => [
                    'lot_id' => $this->id,
                    'column' => $this->column,
                    'row' => $this->row,
                ],
            ],

            // Cluster geometry (when loaded)
            'cluster' => $this->whenLoaded('cluster', function () {
                return [
                    'cluster_id' => $this->cluster->id,
                    'cluster_name' => $this->cluster->cluster_name,
                    'cluster_type' => $this->cluster->cluster_type,
                    'status' => $this->cluster->status,
                ];
            }),

            // Phase geometry (when loaded)
            'phase' => $this->when($this->cluster?->phase, function () {
                return [
                    'phase_id' => $this->cluster->phase->id,
                    'phase_code' => $this->cluster->phase->phase_code,
                    'phase_name' => $this->cluster->phase->phase_name,
                    'status' => $this->cluster->phase->status,
                ];
            }),

            'burials' => $this->burialRecords->map(function ($burial) {
                return [
                    'burial_record_id' => $burial->id,
                    'burial_date' => $burial->deceasedRecord?->date_of_depository,

                    'deceased' => $burial->deceasedRecord ? [
                        'id' => $burial->deceasedRecord->id,
                        'full_name'
                        => $burial->deceasedRecord->first_name . ' '
                            . $burial->deceasedRecord->last_name,
                        'deceased_date' => $burial->deceasedRecord->deceased_date,
                    ] : null,

                    'imported_by' => $burial->user ? [
                        'id' => $burial->user->id,
                        'name' => $burial->user->name,
                    ] : null,
                ];
            }),

        ];
    }
}
