<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClusterResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'type' => 'Feature',
            'geometry' => is_string($this->coordinates)
                ? json_decode($this->coordinates, true)
                : ($this->coordinates ?? ['type' => 'Polygon', 'coordinates' => []]),
            'properties' => [
                'cluster_id' => $this->id,
                'name' => $this->cluster_name,
                'type' => $this->cluster_type,
            ],
        ];
    }
}
