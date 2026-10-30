<?php

namespace App\Http\Requests;

use App\Models\Lot;
use Illuminate\Foundation\Http\FormRequest;

class BurialRecordIndexRequest extends FormRequest
{
    private const ALLOWED_SORTS = [
        'id',
        'deceased_first_name',
        'deceased_date_of_birth',
        'deceased_date_of_death',
        'deceased_date_of_depository',
    ];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string'],
            'filter' => ['nullable', 'string', 'in:all,buried,pending,assigned,unassigned,archived'],
            'disposal' => ['nullable', 'string'],
            'sort_field' => ['nullable', 'string'],
            'sort_direction' => ['nullable', 'string', 'in:asc,desc'],
            'lot_id' => ['nullable', 'integer', 'exists:lots,id'],
        ];
    }

    public function sortField(): string
    {
        return in_array($this->sort_field, self::ALLOWED_SORTS, true)
            ? $this->sort_field
            : 'id';
    }

    public function sortDirection(): string
    {
        return $this->sort_direction ?? 'desc';
    }

    public function filterValue(): string
    {
        return $this->filter ?? 'all';
    }

    public function lotId(): ?int
    {
        return $this->filled('lot_id') ? (int) $this->lot_id : null;
    }

    /**
     * @return array{id: int, column: string, row: string, cluster: string|null, phase: string|null}|null
     */
    public function selectedLot(): ?array
    {
        $lotId = $this->lotId();

        if ($lotId === null) {
            return null;
        }

        $lot = Lot::with('cluster.phase:id,phase_name')->find($lotId);

        if (! $lot) {
            return null;
        }

        return [
            'id' => $lot->id,
            'column' => $lot->column,
            'row' => $lot->row,
            'cluster' => $lot->cluster?->cluster_name,
            'phase' => $lot->cluster?->phase?->phase_name,
        ];
    }
}
