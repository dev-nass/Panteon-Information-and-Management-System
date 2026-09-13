<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'first_name' => $this->first_name,
            'middle_name' => $this->middle_name,
            'last_name' => $this->last_name,
            'full_name' => trim("{$this->first_name} {$this->middle_name} {$this->last_name}"),
            'email' => $this->email,
            'contact_number' => $this->contact_number,
            'role' => $this->role,
            'is_terminated' => (bool) $this->terminated_at,
            'terminated' => $this->when($this->terminated_at, function () {
                return [
                    'at' => $this->terminated_at?->toISOString(),
                    'reason' => $this->terminated_reason,
                    'notes' => $this->terminated_notes,
                    'by' => $this->whenLoaded('terminatedBy', function () {
                        return $this->terminatedBy ? new self($this->terminatedBy) : null;
                    }),
                ];
            }),
        ];
    }
}
