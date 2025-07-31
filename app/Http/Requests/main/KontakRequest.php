<?php

namespace App\Http\Requests\main;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;

class KontakRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_kontak_jenis' => 'required',
            'name'            => 'required',
            'npwp'            => 'required',
            'phone'           => 'required',
            'email'           => 'required|email',
            'address'         => 'required',
            'postal_code'     => 'required',
            'is_company'      => 'required',
        ];
    }

    public function messages(): array
    {
        return [
            'id_kontak_jenis.required' => 'Kontak Jenis is required',
            'name.required'            => 'Name is required',
            'npwp.required'            => 'NPWP is required',
            'phone.required'           => 'Phone is required',
            'email.required'           => 'Email is required',
            'email.email'              => 'Email must be valid',
            'address.required'         => 'Address is required',
            'postal_code.required'     => 'Postal Code is required',
            'is_company.required'      => 'Is Company is required',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $errorMessages = collect($validator->errors()->messages())
            ->map(fn($messages) => $messages[0])
            ->toArray();

        ActivityLogHelper::log('validation_error', 0, $errorMessages);

        return ApiResponseClass::throw('Validation errors', 422, $validator->errors());
    }
}
