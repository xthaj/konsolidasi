<?php

namespace App\Http\Requests;

use App\Models\Wilayah;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Auth;

use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class GetUsersRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Check if user is authenticated
        $user = Auth::user();
        if (!$user) {
            throw new ValidationException(null, response()->json([
                'message' => 'User tidak ditemukan atau belum login.',
                'data' => ['users' => null],
            ], 401));
        }

        // Get validated data
        $level_wilayah = $this->input('level_wilayah');
        $kd_wilayah = $this->input('kd_wilayah');

        // Restrict based on user level
        if ($user->isPusat()) {
            return true;
        } elseif ($user->isProvinsi()) {
            if (!in_array($level_wilayah, ['provinsi', 'kabkot'])) {
                throw new AuthorizationException('Akses tidak diizinkan untuk level wilayah ini.');
            }
            if ($level_wilayah === 'provinsi' && $kd_wilayah !== $user->kd_wilayah) {
                throw new AuthorizationException('Akses dibatasi untuk provinsi Anda.');
            }
            if ($level_wilayah === 'kabkot') {
                $wilayah = Wilayah::where('kd_wilayah', $kd_wilayah)
                    ->where('flag', 3)
                    ->where('parent_kd', $user->kd_wilayah)
                    ->first();
                if (!$wilayah) {
                    throw new AuthorizationException('Akses dibatasi untuk kabupaten/kota di provinsi Anda.');
                }
            }
            return true;
        } elseif ($user->isKabkot()) {
            if ($level_wilayah !== 'kabkot') {
                throw new AuthorizationException('Akses dibatasi untuk kabupaten/kota Anda.');
            }
            if ($kd_wilayah !== $user->kd_wilayah) {
                throw new AuthorizationException('Akses dibatasi untuk kabupaten/kota Anda.');
            }
            return true;
        } else {
            throw new AuthorizationException('Level pengguna tidak valid.');
        }
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'search' => 'nullable|string|max:255',
            // EDIT HERE: Update level_wilayah validation
            'level_wilayah' => 'required|in:semua,semua-provinsi,semua-kabkot,provinsi,kabkot,pusat',
            // EDIT HERE: Adjust kd_wilayah to be required only for provinsi and kabkot
            'kd_wilayah' => [
                'required_if:level_wilayah,provinsi,kabkot',
                'nullable',
                'string',
                'max:4',
                Rule::exists('wilayah', 'kd_wilayah')->where(function ($query) {
                    $level_wilayah = $this->input('level_wilayah');
                    if ($level_wilayah === 'provinsi') {
                        $query->where('flag', 2);
                    } elseif ($level_wilayah === 'kabkot') {
                        $query->where('flag', 3);
                    } else {
                        $query->whereIn('flag', [2, 3]);
                    }
                }),
            ],
            'periode' => 'nullable|string',
        ];
    }

    /**
     * Get custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'level_wilayah.required' => 'Level wilayah wajib dipilih.',
            'level_wilayah.in' => 'Level wilayah tidak valid.',
            'kd_wilayah.required_if' => 'Kode wilayah wajib diisi untuk level wilayah ini.',
            'kd_wilayah.exists' => 'Kode wilayah tidak ditemukan.',
            'kd_wilayah.max' => 'Kode wilayah tidak boleh lebih dari 4 karakter.',
            'search.max' => 'Pencarian tidak boleh lebih dari 255 karakter.',
        ];
    }

    /**
     * Customize failed validation response to match controller format.
     */
    protected function failedValidation(Validator $validator)
    {
        $errorMessages = collect($validator->errors()->all())->implode(', ');
        throw new HttpResponseException(response()->json([
            'message' => "Validation failed: $errorMessages",
            'data' => ['users' => null],
        ], 422));
    }
}
