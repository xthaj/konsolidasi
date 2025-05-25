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


    public function index(Request $request)
    {
        return view('akun.index');
    }


    public function apiUsers(Request $request)
    {
        // Helper: Return error response
        $errorResponse = fn(string $message, int $code) => response()->json([
            'message' => $message,
            'data' => ['users' => null],
        ], $code);

        try {
            // Get authenticated user
            $user = Auth::user();
            if (!$user) {
                return $errorResponse('User tidak ditemukan atau belum login.', 401);
            }

            // Validate request parameters
            $validated = $request->validate([
                'search' => 'nullable|string|max:255',
                'level_wilayah' => 'required|in:semua,semua-provinsi,semua-kabkot,provinsi,kabkot,pusat',
                'kd_wilayah' => [
                    'required_if:level_wilayah,provinsi,kabkot',
                    'nullable', // Allow null for pusat, semua, etc.
                    'string',
                    'max:4',
                    Rule::exists('wilayah', 'kd_wilayah')->where(function ($query) {
                        $query->whereIn('flag', [2, 3]);
                    })->when($request->level_wilayah === 'provinsi', function ($rule) {
                        return $rule->where('flag', 2);
                    })->when($request->level_wilayah === 'kabkot', function ($rule) {
                        return $rule->where('flag', 3);
                    }),
                ],
            ]);

            // Start query with eager-loaded wilayah
            $query = User::with(['wilayah']);

            // Restrict based on user level
            if ($user->isPusat()) {
                // Central Admin: Can access all provinces, cities, or pusat
                switch ($validated['level_wilayah']) {
                    case 'pusat':
                        $query->where('kd_wilayah', '0');
                        break;
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
                    return $errorResponse('Akses tidak diizinkan untuk level wilayah ini.', 403);
                }

                $query->whereIn('level', [2, 3, 4, 5])
                    ->whereHas('wilayah', function ($q) use ($user, $validated) {
                        $q->where('kd_wilayah', $user->kd_wilayah)
                            ->orWhere('parent_kd', $user->kd_wilayah);
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
                    return $errorResponse('Akses tidak diizinkan untuk level wilayah ini.', 403);
                }
                $query->whereIn('level', [4, 5])
                    ->whereHas('wilayah', function ($q) use ($user, $validated) {
                        $q->where('kd_wilayah', $user->kd_wilayah)
                            ->where('kd_wilayah', $validated['kd_wilayah'])
                            ->where('flag', 3);
                    });
            } else {
                return $errorResponse('Level pengguna tidak valid.', 403);
            }

            // Apply active period filter
            if (!empty($validated['periode'])) {
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

            // Handle empty results
            if ($users->isEmpty()) {
                return response()->json([
                    'message' => 'Tidak ada pengguna yang ditemukan dengan filter ini.',
                    'data' => [
                        'users' => [],
                        'current_page' => $users->currentPage(),
                        'last_page' => $users->lastPage(),
                        'total' => $users->total(),
                    ],
                ], 200);
            }

            // Success response
            return response()->json([
                'message' => 'Users retrieved successfully',
                'data' => [
                    'users' => UserResource::collection($users),
                    'current_page' => $users->currentPage(),
                    'last_page' => $users->lastPage(),
                    'total' => $users->total(),
                ],
            ], 200);
        } catch (ValidationException $e) {
            // Combine validation errors into a single message
            $errorMessages = collect($e->errors())->flatten()->implode(', ');
            return $errorResponse("Validation failed: $errorMessages", 422);
        } catch (\Exception $e) {
            Log::error('apiUsers error: ' . $e->getMessage());
            return $errorResponse('An unexpected error occurred: ' . $e->getMessage(), 500);
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

    public function edit(Request $request, $id)
    {
        try {
            // Authenticate user
            $authUser = Auth::user();
            if (!$authUser) {
                return response()->json([
                    'message' => 'User tidak ditemukan atau belum login.',
                    'data' => null,
                ], 401);
            }

            // Call UserService to update user
            $result = $this->userService->updateUser($id, $request, true);

            if (!$result['success']) {
                throw new ValidationException(Validator::make($request->all(), [], [], $result['errors']));
            }

            return response()->json([
                'message' => $result['message'],
                'data' => null,
            ], 200);
        } catch (ValidationException $e) {
            $errorMessages = collect($e->errors())->flatten()->implode(', ');
            return response()->json([
                'message' => "Validation failed: $errorMessages",
                'data' => null,
            ], 422);
        } catch (\Exception $e) {
            Log::error('Edit user error: ' . $e->getMessage());
            return response()->json([
                'message' => 'An unexpected error occurred: ' . $e->getMessage(),
                'data' => null,
            ], $e->getCode() ?: 500);
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

        return response()->json(['message' => 'User berhasil dihapus']);
    }
}
