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
     * NOTE: Previously used, but chatGPT changed the approach to Lot -> burialRecord -> deecasedRecord
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'burial_date' => $this->burial_date,

            'lot' => $this->lot ? [
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
            ] : null,

            'deceased' => $this->deceasedRecord ? [
                'id' => $this->deceasedRecord->id,
                'full_name' => $this->deceasedRecord->first_name . ' ' . $this->deceasedRecord->last_name,
                'deceased_date' => $this->deceasedRecord->deceased_date,
            ] : null,

            'imported_by' => $this->user ? [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ] : null,
        ];
    }
}
