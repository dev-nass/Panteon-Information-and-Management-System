<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UserTerminateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'terminated_reason' => ['required', 'in:resigned,retired,terminated,end_of_contract,transferred,other'],
            'terminated_notes' => ['nullable', 'string', 'max:1000', 'required_if:terminated_reason,other'],
        ];
    }

    public function messages(): array
    {
        return [
            'terminated_notes.required_if' => 'Please provide details for \'Other\' reason.',
        ];
    }
}
