<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class UserService
{
    public function createUser(Request $request, bool $isApi = false): array
    {
        // Define validation rules
        $rules = [
            'nama_lengkap' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'min:7', 'max:255', 'unique:user,username', 'regex:/^[a-zA-Z0-9_]+$/'],
            'password' => ['required', 'string', 'min:6', 'max:255'],
            'kd_wilayah' => ['required', 'string', 'max:6'],
            'level' => ['required', 'integer', 'in:0,1,2,3,4,5'],
        ];

        // Custom error messages
        $messages = [
            'username.min' => 'Username harus lebih dari 6 karakter.',
            'username.max' => 'Username terlalu panjang.',
            'username.unique' => 'Username sudah digunakan.',
            'username.regex' => 'Username hanya boleh berisi huruf, angka, dan underscore.',
            'password.min' => 'Password minimal sepanjang 6 karakter.',
            'password.max' => 'Password terlalu panjang.',
            'kd_wilayah.required' => 'Satuan kerja belum dipilih.',
            'level.in' => 'Level pengguna tidak valid.',
        ];

        // Validate request
        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            if ($isApi) {
                throw new ValidationException($validator);
            }
            return [
                'success' => false,
                'errors' => $validator->errors(),
                'input' => $request->all(),
            ];
        }

        // Create user
        $user = User::create([
            'username' => $request->username,
            'password' => Hash::make($request->password),
            'nama_lengkap' => $request->nama_lengkap,
            'level' => $request->level,
            'kd_wilayah' => $request->kd_wilayah,
        ]);

        return [
            'success' => true,
            'user' => $user,
        ];
    }
}
