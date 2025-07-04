<?php

namespace App\Http\Requests;

use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\App;

//del later
use App\Models\User;

class UpdateRekonsiliasiRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize()
    {
        Log::info('UpdateRekonsiliRequest auth', [
            'input' => $this->all(),
            'json' => $this->json()->all(),
            'raw_content' => $this->getContent(),
        ]);
        $userId = $this->json('user_id') ?? $this->input('user_id');
        if (App::environment('local') && filled($userId)) {
            Log::info('Local environment with user_id', ['user_id' => $userId]);
            return User::find($userId) !== null; // Validate user exists
        } else {
            Log::info('Falling back to Auth check');
            return Auth::check();
        }
    }

    protected function prepareForValidation(): void
    {
        // Only allow injecting user_id in local environment
        if (App::environment('local') && $this->filled('user_id')) {
            Log::info('Allowing user_id in local', ['user_id' => $this->input('user_id')]);
            return; // allow passed-in user_id
        }

        // Otherwise (production, staging, etc), enforce Auth user
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
