<?php

namespace App\Http\Requests\Teacher;

use Illuminate\Foundation\Http\FormRequest;

class StoreTeacherRequest extends FormRequest
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
          'username' => 'required|unique:teachers',
          'email' => 'email|nullable|unique:teachers',
          'password' => 'required|min:6|regex:/^(?=.*\d.*\d)[0-9A-Za-z]{6,}$/',
        ];
    }
}
