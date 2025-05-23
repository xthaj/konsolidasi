<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Services\UserService;
use App\Http\Resources\UserResource;
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

    // public function index(Request $request)
    // {
    //     $query = User::with('wilayah');

    //     if ($request->has('wilayah_level')) {
    //         if ($request->wilayah_level === 'pusat') {
    //             $query->whereIn('level', [0, 1]); // Admin or Operator Pusat
    //         } elseif ($request->wilayah_level === 'provinsi') {
    //             $query->whereIn('level', [2, 3]);
    //             if ($request->filled('kd_wilayah')) {
    //                 $query->whereHas('wilayah', function ($q) use ($request) {
    //                     $q->where('kd_wilayah', $request->kd_wilayah)->where('flag', 2);
    //                 });
    //             } else {
    //                 $query->whereHas('wilayah', function ($q) {
    //                     $q->where('flag', 2);
    //                 });
    //             }
    //         } elseif ($request->wilayah_level === 'kabkot') {
    //             $query->whereIn('level', [4, 5]);
    //             if ($request->filled('kd_wilayah')) {
    //                 $query->whereHas('wilayah', function ($q) use ($request) {
    //                     $q->where('kd_wilayah', $request->kd_wilayah)->where('flag', 3);
    //                 });
    //             } else {
    //                 $query->whereHas('wilayah', function ($q) {
    //                     $q->where('flag', 3);
    //                 });
    //             }
    //         }
    //     }

    //     if ($request->has('search')) {
    //         $search = $request->search;
    //         $query->where(function ($q) use ($search) {
    //             $q->where('username', 'like', '%' . $search . '%')
    //                 ->orWhere('nama_lengkap', 'like', '%' . $search . '%');
    //         });
    //     }

    //     $users = $query->paginate(100);
    //     return view('akun.index', ['users' => UserResource::collection($users)]);
    // }

    public function index(Request $request)
    {
        return view('akun.index');
    }


    public function apiUsers(Request $request)
    {
        // Helper: Return error response
        $errorResponse = fn(string $message, string $status, int $code) => response()->json([
            'message' => $message,
            'status' => $status,
            'data' => [
                'users' => null,
            ],
        ], $code);

        try {
            // Get authenticated user
            $user = Auth::user();
            if (!$user) {
                return $errorResponse('User tidak ditemukan atau belum login.', 'unauthenticated', 401);
            }

            // Validate request parameters
            $validated = $request->validate([
                'search' => 'nullable|string|max:255',
                'level_wilayah' => 'required|in:semua,semua-provinsi,semua-kabkot,provinsi,kabkot',
                'kd_wilayah' => [
                    'required_if:level_wilayah,provinsi,kabkot',
                    'string',
                    'max:4',
                    'exists:wilayah,kd_wilayah',
                ],
            ]);

            // Start query with eager-loaded wilayah
            $query = User::with(['wilayah']);

            // Restrict based on user level
            if ($user->isPusat()) {
                // Central Admin: Can access all provinces or cities
                switch ($validated['level_wilayah']) {
                    case 'semua':
                        // No level filter
                        break;
                    case 'semua-provinsi':
                        $query->whereIn('level', [2, 3]);
                        break;
                    case 'semua-kabkot':
                        $query->whereIn('level', [4, 5]);
                        break;
                    case 'provinsi':
                        $query->whereIn('level', [2, 3])
                            ->whereHas('wilayah', function ($q) use ($validated) {
                                $q->where('kd_wilayah', $validated['kd_wilayah'])
                                    ->where('flag', 2);
                            });
                        break;
                    case 'kabkot':
                        $query->whereIn('level', [4, 5])
                            ->whereHas('wilayah', function ($q) use ($validated) {
                                $q->where('kd_wilayah', $validated['kd_wilayah'])
                                    ->where('flag', 3);
                            });
                        break;
                }
            } elseif ($user->isProvinsi()) {
                // Province User: Can access own province or its cities
                if (!in_array($validated['level_wilayah'], ['provinsi', 'kabkot', 'semua-kabkot'])) {
                    return $errorResponse('Akses tidak diizinkan untuk level wilayah ini.', 'forbidden', 403);
                }

                $query->whereIn('level', [2, 3, 4, 5])
                    ->whereHas('wilayah', function ($q) use ($user, $validated) {
                        $q->where('kd_wilayah', $user->kd_wilayah) // Own province
                            ->orWhere('parent_kd', $user->kd_wilayah); // Its cities
                        if ($validated['level_wilayah'] === 'provinsi') {
                            $q->where('kd_wilayah', $validated['kd_wilayah'])
                                ->where('flag', 2);
                        } elseif ($validated['level_wilayah'] === 'kabkot') {
                            $q->where('kd_wilayah', $validated['kd_wilayah'])
                                ->where('flag', 3)
                                ->where('parent_kd', $user->kd_wilayah);
                        } else {
                            $q->whereIn('flag', [2, 3]);
                        }
                    });
            } elseif ($user->isKabkot()) {
                // City User: Can access own city only
                if ($validated['level_wilayah'] !== 'kabkot') {
                    return $errorResponse('Akses tidak diizinkan untuk level wilayah ini.', 'forbidden', 403);
                }
                $query->whereIn('level', [4, 5])
                    ->whereHas('wilayah', function ($q) use ($user, $validated) {
                        $q->where('kd_wilayah', $user->kd_wilayah)
                            ->where('kd_wilayah', $validated['kd_wilayah'])
                            ->where('flag', 3);
                    });
            } else {
                return $errorResponse('Level pengguna tidak valid.', 'invalid_level', 403);
            }

            // Apply active period filter (adjust based on your schema)
            if (!empty($validated['periode'])) {
                // Assuming a 'periode' column in the user table; adjust as needed
                $query->where('periode', $validated['periode']);
            }

            // Apply search filter
            if (!empty($validated['search'])) {
                $query->where(function ($q) use ($validated) {
                    $q->where('username', 'like', '%' . $validated['search'] . '%')
                        ->orWhere('nama_lengkap', 'like', '%' . $validated['search'] . '%');
                });
            }

            // Paginate results
            $users = $query->paginate(100);

            // Return JSON response with success message
            return response()->json([
                'message' => 'Users retrieved successfully',
                'status' => 'success',
                'data' => [
                    'users' => UserResource::collection($users),
                    'current_page' => $users->currentPage(),
                    'last_page' => $users->lastPage(),
                    'total' => $users->total(),
                ],
            ], 200);
        } catch (ValidationException $e) {
            return $errorResponse('Validation failed', 'validation_error', 422, ['errors' => $e->errors()]);
        } catch (\Exception $e) {
            Log::error('apiUsers error: ' . $e->getMessage());
            return $errorResponse('An unexpected error occurred', 'server_error', 500, ['error' => $e->getMessage()]);
        }
    }

    public function getUsers(Request $request)
    {
        $query = User::with('wilayah');

        if ($request->has('kd_wilayah')) {
            $query->where('kd_wilayah', $request->kd_wilayah);
        }

        $users = $query->paginate(100);

        return response()->json([
            'data' => UserResource::collection($users),
            'current_page' => $users->currentPage(),
            'last_page' => $users->lastPage(),
            'total' => $users->total(),
        ]);
    }

    public function store(Request $request)
    {
        try {
            $result = $this->userService->createUser($request, true);
            return response()->json(new UserResource($result['user']), 201);
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
            'username' => ['required', 'string', 'max:255', Rule::unique('user', 'username')->ignore($user_id, 'user_id')],
            'nama_lengkap' => ['required', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'min:8'],
            'level' => ['required', 'integer', 'between:0,5'],
            'kd_wilayah' => ['nullable', 'string', 'exists:wilayah,kd_wilayah'],
        ]);

        $user->update([
            'username' => $validated['username'],
            'nama_lengkap' => $validated['nama_lengkap'],
            'password' => $request->password ? Hash::make($validated['password']) : $user->password,
            'level' => $validated['level'],
            'kd_wilayah' => $validated['kd_wilayah'] ?? $user->kd_wilayah,
        ]);

        return response()->json(new UserResource($user->load('wilayah')));
    }

    public function destroy($user_id)
    {
        $user = User::findOrFail($user_id);
        $user->delete();

        return response()->json(['message' => 'User deleted successfully']);
    }
}
