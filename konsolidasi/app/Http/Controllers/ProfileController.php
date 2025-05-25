<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(): View
    {
        $user = Auth::user();
        $username = $user->username;
        $nama_lengkap = $user->nama_lengkap;
        $nama_wilayah = $user->Wilayah::getWilayahName($user->kd_wilayah);


        return view('profile.edit', [
            'username' => $username,
            'nama_lengkap' => $nama_lengkap,
            'nama_wilayah' => $nama_wilayah,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    public function updatePassword(Request $request): JsonResponse
    {
        try {
            // Define validation rules
            $rules = [
                'password' => ['required', 'string', 'min:6', 'confirmed'],
                'user_id' => ['sometimes', 'integer', 'exists:user,user_id'],
            ];

            // Custom error messages
            $messages = [
                'password.required' => 'Password wajib diisi.',
                'password.min' => 'Password minimal sepanjang 6 karakter.',
                'password.confirmed' => 'Password dan konfirmasi password berbeda.',
                'user_id.exists' => 'Pengguna tidak ditemukan.',
            ];

            // Validate request
            $request->validate($rules, $messages);

            // Determine which user to update
            $user = $request->has('user_id')
                ? User::findOrFail($request->user_id)
                : $request->user();

            if (!$user) {
                return response()->json([
                    'message' => 'Pengguna tidak ditemukan.',
                ], 404);
            }

            // Update password
            $user->password = Hash::make($request->password);
            $user->save();

            return response()->json([
                'message' => 'Kata sandi berhasil diperbarui.',
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validasi gagal.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Pengguna tidak ditemukan.',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Password update failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Terjadi kesalahan saat memperbarui kata sandi.',
            ], 500);
        }
    }
}
