<?php

namespace App\Http\Requests\Clerk;

use Illuminate\Foundation\Http\FormRequest;

class BurialRecordUpdateRequest extends FormRequest
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
            'deceased.first_name' => 'required|string|max:255',
            'deceased.middle_name' => 'nullable|string|max:255',
            'deceased.last_name' => 'required|string|max:255',
            'deceased.age' => 'nullable|integer',
            'deceased.birth.date' => 'nullable|date',
            'deceased.civil_status' => 'nullable|string|max:255',
            'deceased.religion' => 'nullable|string|max:255',
            'deceased.nationality' => 'nullable|string|max:255',
            'deceased.occupation.name' => 'nullable|string|max:255',
            'deceased.address' => 'nullable|string|max:255',
            'deceased.lgbtq' => 'nullable|string|max:255',
            'deceased.precinct_num' => 'nullable|integer',
            'deceased.death.date' => 'nullable|date',
            'deceased.death.cause' => 'nullable|string|max:255',
            'deceased.death.place' => 'nullable|string|max:255',
            'deceased.corpse_disposal' => 'nullable|string|max:255',
            'deceased.cremation.place' => 'nullable|string|max:255',
            'deceased.cremation.date' => 'nullable|date',
            'deceased.burial_place' => 'nullable|string|max:255',
            'deceased.burial.date' => 'nullable|date',
            'deceased.family.father' => 'nullable|string|max:255',
            'deceased.family.mother_maiden' => 'nullable|string|max:255',
            'deceased.occupation.address' => 'nullable|string|max:255',
            'deceased.occupation.supervisor' => 'nullable|string|max:255',
            'deceased.applicant.first_name' => 'required|string|max:255',
            'deceased.applicant.middle_name' => 'nullable|string|max:255',
            'deceased.applicant.last_name' => 'required|string|max:255',
            'deceased.applicant.contact_number' => ['nullable', 'string', 'regex:/^09\d{9}$/'],
            'lot_id' => 'nullable|exists:lots,id',
        ];
    }

    public function messages(): array
    {
        return [
            'deceased.applicant.contact_number.regex' => 'Contact number must start with 09 and be 11 digits long (e.g. 09171234567).',
        ];
    }
}
