<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

class ScamLeadRequest extends \App\Foundation\FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:250'],
            'email' => ['nullable', 'string', 'email'],
            'country_code' => ['nullable', 'string', $this->countryValidationRules()],
            'phone_number' => ['required', 'numeric', 'digits_between:9,14'],
            'scam_source_id' => ['nullable', 'integer', Rule::exists('scam_sources', 'id')],
            'scam_type_id' => ['nullable', 'integer', Rule::exists('scam_types', 'id')],
            'scam_amount' => ['nullable', 'numeric', 'min:1', 'max:999999999999'],
            'customer_description' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
