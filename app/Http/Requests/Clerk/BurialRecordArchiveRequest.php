<?php

namespace App\Http\Requests\Clerk;

use Illuminate\Foundation\Http\FormRequest;

class BurialRecordArchiveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'archived_reason' => ['required', 'in:pull_out,transfer,expired,other'],
            'archived_notes' => ['nullable', 'string', 'max:1000', 'required_if:archived_reason,other'],
        ];
    }
}
