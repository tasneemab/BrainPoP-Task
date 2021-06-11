<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;

class StoreStudentRequest extends FormRequest
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
          'name' => 'required',
          'username' => 'required|unique:students',
          'password' => 'required|min:6|regex:/^(?=.*\d.*\d)[0-9A-Za-z]{6,}$/',
          'grade'=>'required|numeric|between:0,12',
          'periods' => 'array',
          'periods.*' => 'numeric'
        ];
    }
}
