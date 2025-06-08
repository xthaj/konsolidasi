<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateRekonsiliasiRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize()
    {
        return Auth::check(); // Require authenticated user
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules()
    {
        return [
            'alasan' => 'required|string|max:500',
            'detail' => 'nullable|string',
            'media' => 'nullable|url',
            'user_id' => 'nullable|exists:user,user_id',
        ];
    }

    public function messages()
    {
        return [
            'alasan.required' => 'Alasan harus diisi.',
        ];
    }
}
