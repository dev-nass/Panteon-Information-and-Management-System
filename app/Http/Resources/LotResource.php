<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LotResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, // added so composable dont need to change
            'lot' => [
                'type' => 'Feature',
                'geometry' => $this->coordinates ?? [
                    'type' => 'Polygon',
                    'coordinates' => [],
                ],
                "properties" => [

                    'lot_id' => $this->id,
                    'lot_number' => $this->lot_number,
                    'lot_type' => $this->lot_type,
                    'status' => $this->status,
                    'total_capacity' => $this->total_capacity,
                ],

            ],

            'burials' => $this->burialRecords->map(function ($burial) {
                return [
                    'id' => $burial->id,
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
