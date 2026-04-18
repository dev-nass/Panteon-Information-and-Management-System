<?php

namespace App\Http\Requests\Clerk;

use Illuminate\Foundation\Http\FormRequest;

class BurialRecordStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // deceased fields
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'age' => 'nullable|integer',
            'birth_date' => 'nullable|date',
            'civil_status' => 'nullable|string|max:255',
            'religion' => 'nullable|string|max:255',
            'nationality' => 'nullable|string|max:255',
            'occupation_name' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'lgbtq' => 'nullable|string|max:255',
            'precinct_num' => 'nullable|integer',
            'death_date' => 'required|date',
            'death_cause' => 'nullable|string|max:255',
            'death_place' => 'nullable|string|max:255',
            'corpse_disposal' => 'nullable|string|max:255',
            'cremation_place' => 'nullable|string|max:255',
            'cremation_date' => 'nullable|date',
            'burial_place' => 'nullable|string|max:255',
            'burial_date' => 'nullable|date',
            'father_name' => 'nullable|string|max:255',
            'mother_maiden_name' => 'nullable|string|max:255',
            'company_address' => 'nullable|string|max:255',
            'company_supervisor' => 'nullable|string|max:255',

            // applicant fields
            'applicant_first_name' => 'required|string|max:255',
            'applicant_middle_name' => 'nullable|string|max:255',
            'applicant_last_name' => 'required|string|max:255',
            'applicant_contact_number' => 'required', 'string', 'regex:/^09\d{9}$/',
        ];
    }

    public function messages(): array
    {
        return [
            'applicant_contact_number.regex' => 'Contact number must start with 09 and be 11 digits long (e.g. 09171234567).',
        ];
    }

    public function deceasedData(): array
    {
        return $this->only([
            'first_name', 'middle_name', 'last_name', 'age',
            'birth_date', 'civil_status', 'religion', 'nationality',
            'occupation_name', 'address', 'lgbtq', 'precinct_num',
            'death_date', 'death_cause', 'death_place', 'corpse_disposal',
            'cremation_place', 'cremation_date', 'burial_place', 'burial_date',
            'father_name', 'mother_maiden_name', 'company_address', 'company_supervisor',
        ]);
    }

    public function applicantData(): array
    {
        return $this->only([
            'applicant_first_name', 'applicant_middle_name',
            'applicant_last_name',  'applicant_contact_number',
        ]);
    }
}
