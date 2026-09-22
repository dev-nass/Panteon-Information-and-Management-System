<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UserReinstateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reinstated_reason' => ['required', 'in:rehired,contract_renewed,error_correction,appeal_approved,other'],
            'reinstated_notes' => ['nullable', 'string', 'max:1000', 'required_if:reinstated_reason,other'],
        ];
    }

    public function messages(): array
    {
        return [
            'reinstated_notes.required_if' => 'Please provide details for \'Other\' reason.',
        ];
    }
}
