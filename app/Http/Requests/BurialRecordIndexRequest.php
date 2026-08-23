<?php

namespace App\Http\Requests;

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
            'filter' => ['nullable', 'string'],
            'disposal' => ['nullable', 'string'],
            'sort_field' => ['nullable', 'string', 'in:'.implode(',', self::ALLOWED_SORTS)],
            'sort_direction' => ['nullable', 'string', 'in:asc,desc'],
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
}
