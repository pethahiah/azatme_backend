<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserKYCRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'identity_card' => 'file|mimes:jpeg,png,pdf|max:2048',
            'utility_bill' => 'file|mimes:jpeg,png,pdf|max:2048',
            'proof_of_address' => 'file|mimes:jpeg,png,pdf|max:2048',
            'business_registration_certificate' => 'file|mimes:jpeg,png,pdf|max:2048',
            'business_registration_number' => 'string|max:255',
            'automatic_verification' => 'boolean',
        ];
    }
}
