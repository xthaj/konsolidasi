<?php

namespace App\Services;

use App\Models\User;
use App\Models\Wilayah;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
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
            'username' => strtolower($request->username),
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

    public function updateUser(int $userId, Request $request, bool $isApi = false): array
    {
        $user = User::find($userId);
        if (!$user) {
            if ($isApi) {
                throw new \Exception('User tidak ditemukan.', 404);
            }
            return [
                'success' => false,
                'errors' => ['user' => 'User tidak ditemukan.'],
            ];
        }

        // Define validation rules for each attribute
        $rules = [
            'username' => ['sometimes', 'string', 'min:7', 'max:255', 'regex:/^[a-zA-Z0-9_]+$/', Rule::unique('user', 'username')->ignore($user->id)],
            'nama_lengkap' => ['sometimes', 'string', 'max:255'],
            'password' => ['sometimes', 'string', 'min:6', 'max:255', 'confirmed'],
            'kd_wilayah' => ['sometimes', 'string', 'max:6', 'exists:wilayah,kd_wilayah'],
            'level' => ['sometimes', 'integer', 'in:0,1,2,3,4,5'],
        ];

        // Custom error messages
        $messages = [
            'username.min' => 'Username harus lebih dari 6 karakter.',
            'username.max' => 'Username terlalu panjang.',
            'username.unique' => 'Username sudah digunakan.',
            'username.regex' => 'Username hanya boleh berisi huruf, angka, dan underscore.',
            'password.min' => 'Password minimal sepanjang 6 karakter.',
            'password.max' => 'Password terlalu panjang.',
            'password.confirmed' => 'Password dan konfirmasi password berbeda.',
            'kd_wilayah.max' => 'Kode wilayah terlalu panjang.',
            'kd_wilayah.exists' => 'Kode wilayah tidak valid.',
            'level.in' => 'Level pengguna tidak valid.',
        ];

        // Validate only provided attributes
        $validator = Validator::make($request->all(), array_intersect_key($rules, $request->all()), $messages);

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

        // Prepare update data
        $updateData = [];
        if ($request->has('username')) {
            $updateData['username'] = strtolower($request->username);
        }
        if ($request->has('nama_lengkap')) {
            $updateData['nama_lengkap'] = $request->nama_lengkap;
        }
        if ($request->has('password')) {
            $updateData['password'] = Hash::make($request->password);
        }
        if ($request->has('kd_wilayah')) {
            $updateData['kd_wilayah'] = $request->kd_wilayah;

            // Determine the new level based on current level and flag from Wilayah
            $currentLevel = $user->level;
            $flag = 1; // Default to pusat (flag=1) if kd_wilayah is null or not found

            if ($request->kd_wilayah) {
                $wilayah = Wilayah::where('kd_wilayah', $request->kd_wilayah)->first();
                if ($wilayah) {
                    $flag = $wilayah->flag; // Get flag from Wilayah (2 for provinsi, 3 for kabkot)
                } else {
                    if ($isApi) {
                        throw new \Exception('Wilayah tidak ditemukan.', 404);
                    }
                    return [
                        'success' => false,
                        'errors' => ['kd_wilayah' => 'Wilayah tidak ditemukan.'],
                    ];
                }
            }

            // Map levels based on current level and target region (flag)
            if (in_array($currentLevel, [0, 1])) { // From pusat
                if ($flag == 2) { // To provinsi
                    $updateData['level'] = $currentLevel == 0 ? 2 : 3; // Admin pusat (0) -> Admin provinsi (2), Operator pusat (1) -> Operator provinsi (3)
                } elseif ($flag == 3) { // To kabkot
                    $updateData['level'] = $currentLevel == 0 ? 4 : 5; // Admin pusat (0) -> Admin kabkot (4), Operator pusat (1) -> Operator kabkot (5)
                }
            } elseif (in_array($currentLevel, [2, 3])) { // From provinsi
                if ($flag == 1) { // To pusat
                    $updateData['level'] = $currentLevel == 2 ? 0 : 1; // Admin provinsi (2) -> Admin pusat (0), Operator provinsi (3) -> Operator pusat (1)
                } elseif ($flag == 3) { // To kabkot
                    $updateData['level'] = $currentLevel == 2 ? 4 : 5; // Admin provinsi (2) -> Admin kabkot (4), Operator provinsi (3) -> Operator kabkot (5)
                }
            } elseif (in_array($currentLevel, [4, 5])) { // From kabkot
                if ($flag == 1) { // To pusat
                    $updateData['level'] = $currentLevel == 4 ? 0 : 1; // Admin kabkot (4) -> Admin pusat (0), Operator kabkot (5) -> Operator pusat (1)
                } elseif ($flag == 2) { // To provinsi
                    $updateData['level'] = $currentLevel == 4 ? 2 : 3; // Admin kabkot (4) -> Admin provinsi (2), Operator kabkot (5) -> Operator provinsi (3)
                }
            }
        }
        if ($request->has('level') && !isset($updateData['level'])) {
            $updateData['level'] = $request->level; // Only set level if not already set by kd_wilayah logic
        }

        // Update user
        $user->update($updateData);

        return [
            'success' => true,
            'user' => $user,
            'message' => 'User berhasil diperbarui.',
        ];
    }
}
