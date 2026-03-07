<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BurialRecordResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     * NOTE: Previously used, but chatGPT changed the approach to Lot -> burialRecord -> deecasedRecord and
     * now using LotResource, BUT this is now used for Burial Records Table
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'burial' => [
                'date' => $this->burial_date,
            ],

            'lot' => $this->whenLoaded('lot', function () {
                return [
                    'type' => 'Feature',
                    'geometry' => $this->lot->coordinates ?? [
                        'type' => 'Polygon',
                        'coordinates' => [],
                    ],
                    'properties' => [
                        'lot_id' => $this->lot->id,
                        'lot_number' => $this->lot->lot_number,
                        'lot_type' => $this->lot->lot_type,
                        'status' => $this->lot->status,
                    ],
                ];
            }),

            'deceased' => new DeceasedRecordResource(
                $this->whenLoaded('deceasedRecord')
            ),

            'imported_by' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                ];
            }),
        ];
    }
}
