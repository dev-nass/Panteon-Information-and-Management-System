<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BurialRecordResource extends JsonResource
{
    /**
     * Description: Used for the Burial Record table index and show
     * NOTE: v1 Previously used, but chatGPT changed the approach to Lot -> burialRecord -> deecasedRecord and;
     * Note: v2 now using LotResource, BUT this is now used for Burial Records Table
     */
    public function toArray(Request $request): array
    {
        return [
            'burial' => [
                'id' => $this->id,
            ],

            'cluster' => $this->whenLoaded('lot', function () {
                if ($this->lot && $this->lot->relationLoaded('cluster')) {
                    return new ClusterResource($this->lot->cluster);
                }
                return null;
            }),

            'lot' => $this->whenLoaded('lot', function () {
                return new LotResource($this->lot);
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
