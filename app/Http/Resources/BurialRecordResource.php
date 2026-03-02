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
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'burial_date' => $this->burial_date,

            'lot' => [
                'id' => $this->lot->id,
                'lot_number' => $this->lot->lot_number,
                'coordinates' => $this->lot->coordinates,
            ],

            'deceased' => [
                'id' => $this->deceasedRecord->id,
                'full_name' => $this->deceasedRecord->first_name . ' ' . $this->deceasedRecord->last_name,
                'deceased_date' => $this->deceasedRecord->deceased_date,
            ],

            'imported_by' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ],
        ];
    }
}
