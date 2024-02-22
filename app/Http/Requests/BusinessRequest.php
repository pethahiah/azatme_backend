<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BusinessRequest extends FormRequest
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
    public function rules()
    {
        return [
            //
            'business_name' => 'required|string',
            'business_email' => 'required|string|unique:businesses',
            'business_number' => 'string|unique:businesses',
            //'business_logo' => 'mimes:jpeg,jpg,png,gif|required|max:10000',
            'business_address' => 'required|string',
            'owner_id' => 'required|integer'
        ];
    }
}
