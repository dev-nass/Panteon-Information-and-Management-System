<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PhaseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
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
                    // 'total_capacity' => $this->total_capacity,
                ],
            ],
        ];
    }
}