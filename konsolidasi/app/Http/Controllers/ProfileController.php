<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use App\Models\Wilayah;
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
            $request->validate([
                'password' => ['required', 'min:6', 'confirmed'],
            ]);

            $user = $request->user();
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
        } catch (\Exception $e) {
            Log::error('Password update failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Terjadi kesalahan saat memperbarui kata sandi.',
            ], 500);
        }
    }
}
