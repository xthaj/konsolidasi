<?php

namespace App\Http\Requests;

use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateRekonsiliasiRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize()
    {
        // Log::info('UpdateRekonsiliRequest auth', [
        //     'input' => $this->all(),
        //     'json' => $this->json()->all(),
        //     'raw_content' => $this->getContent(),
        // ]);

        return Auth::check();
    }

    protected function prepareForValidation(): void
    {
        if (Auth::check()) {
            Log::info('Merging Auth user_id', ['auth_id' => Auth::id()]);
            $this->merge([
                'user_id' => Auth::id(),
            ]);
        }
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
        ];
    }

    public function messages()
    {
        return [
            'alasan.required' => 'Alasan harus diisi.',
        ];
    }
}
