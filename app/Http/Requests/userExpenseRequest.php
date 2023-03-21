<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class userExpenseRequest extends FormRequest
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
           'bankName' => 'required|string',
           'bankCode' => 'required|min:6|max:6',
            'account_number' => 'required|min:10|max:10'
            
        ];
    }
}
