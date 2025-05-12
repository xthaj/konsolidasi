<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AkunController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }
    public function index(Request $request)
    {
        $query = User::with('wilayah');

        if ($request->has('wilayah_level')) {
            if ($request->wilayah_level === 'pusat') {
                $query->where('is_pusat', true);
            } elseif ($request->wilayah_level === 'provinsi') {
                if ($request->filled('kd_wilayah_provinsi')) {
                    $query->where('kd_wilayah', $request->kd_wilayah_provinsi);
                } else {
                    // $query->whereRaw('LEN(kd_wilayah) = 2');
                    // MYSQL/MS SQL DB BRANCH
                    $query->whereRaw('CHAR_LENGTH(kd_wilayah) = 2');
                }
            } elseif ($request->wilayah_level === 'kabkot') {
                if ($request->filled('kd_wilayah')) {
                    $query->where('kd_wilayah', $request->kd_wilayah);
                } else {
                    // $query->whereRaw('LEN(kd_wilayah) = 4');
                    $query->whereRaw('CHAR_LENGTH(kd_wilayah) = 4');
                }
            }
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('username', 'like', '%' . $search . '%')
                    ->orWhere('nama_lengkap', 'like', '%' . $search . '%');
            });
        }

        $users = $query->paginate(100);
        return view('akun.index', compact('users'));
    }

    public function getUsers(Request $request)
    {
        $query = User::with('wilayah');

        if ($request->has('kd_wilayah')) {
            $query->where('kd_wilayah', $request->kd_wilayah);
        }

        $users = $query->paginate(100);

        return response()->json([
            'data' => $users->items(),
            'current_page' => $users->currentPage(),
            'last_page' => $users->lastPage(),
            'total' => $users->total(),
        ]);
    }



    public function store(Request $request)
    {
        // dd($request->all());
        try {
            // Create user using UserService
            $result = $this->userService->createUser($request, true);

            return response()->json($result['user'], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An unexpected error occurred',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $user_id)
    {
        $user = User::findOrFail($user_id);

        $validated = $request->validate([
            'username' => ['required', 'string', 'max:255'],
            'nama_lengkap' => ['required', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'min:8'],
            'is_admin' => ['boolean'],
            'kd_wilayah' => ['nullable', 'string', 'exists:wilayah,kd_wilayah'],
        ]);

        $user->update([
            'username' => $validated['username'],
            'nama_lengkap' => $validated['nama_lengkap'],
            'password' => $request->password ? Hash::make($validated['password']) : $user->password,
            'is_admin' => $validated['is_admin'] ?? $user->is_admin,
            'kd_wilayah' => $validated['kd_wilayah'] ?? $user->kd_wilayah,
            'is_pusat' => !$validated['kd_wilayah'],
        ]);

        return response()->json($user->load('wilayah'));
    }

    public function destroy($user_id)
    {
        $user = User::findOrFail($user_id);
        $user->delete();

        return response()->json(['message' => 'User deleted successfully']);
    }
}
