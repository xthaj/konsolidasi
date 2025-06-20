<?php

namespace App\Http\Controllers;

use App\Http\Requests\GetUsersRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Models\Wilayah;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use App\Services\UserService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function index()
    {
        return view('user.index');
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
            // Log::error('Edit user error: ' . $e->getMessage());
            return response()->json([
                'message' => 'An unexpected error occurred: ' . $e->getMessage(),
                'data' => null,
            ], $e->getCode() ?: 500);
        }
    }

    public function destroy($user_id)
    {
        $user = User::findOrFail($user_id);
        $user->delete();

        return response()->json(['message' => 'User berhasil dihapus']);
    }


    public function getUserWilayah(): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        // Determine the wilayah level
        $wilayahLevel = 'kabkot'; // Default for backwards compatibility
        if ($user->isPusat()) {
            $wilayahLevel = 'pusat';
        } elseif ($user->isProvinsi()) {
            $wilayahLevel = 'provinsi';
        }

        // Fetch kd_parent from the wilayah table
        $kdParent = null;
        if ($user->kd_wilayah) {
            $wilayah = Wilayah::where('kd_wilayah', $user->kd_wilayah)->first();
            $kdParent = $wilayah ? $wilayah->parent_kd : null;
        }

        return response()->json([
            'success' => true,
            'data' => [
                // 'id' => $user->id,
                'kd_wilayah' => $user->kd_wilayah,
                'is_provinsi' => $user->isProvinsi(),
                'wilayah_level' => $wilayahLevel,
                'is_pusat' => $user->isPusat(),
                'kd_parent' => $kdParent,
            ],
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

    // public function update(Request $request, $user_id)
    // {
    //     $user = User::findOrFail($user_id);

    //     $validated = $request->validate([
    //         'username' => ['required', 'string', 'max:255', Rule::unique('user', 'username')->ignore($user_id, 'user_id')],
    //         'nama_lengkap' => ['required', 'string', 'max:255'],
    //         'password' => ['nullable', 'string', 'min:8'],
    //         'level' => ['required', 'integer', 'between:0,5'],
    //         'kd_wilayah' => ['nullable', 'string', 'exists:wilayah,kd_wilayah'],
    //     ]);

    //     $user->update([
    //         'username' => $validated['username'],
    //         'nama_lengkap' => $validated['nama_lengkap'],
    //         'password' => $request->password ? Hash::make($validated['password']) : $user->password,
    //         'level' => $validated['level'],
    //         'kd_wilayah' => $validated['kd_wilayah'] ?? $user->kd_wilayah,
    //     ]);

    //     return response()->json(new UserResource($user->load('wilayah')));
    // }

    public function getUsers(GetUsersRequest $request): JsonResponse
    {
        // Helper: Return error response
        $errorResponse = fn(string $message, int $code) => response()->json([
            'message' => $message,
            'data' => ['users' => null],
        ], $code);

        try {
            // Get validated data
            $validated = $request->validated();

            // Start query with eager-loaded wilayah
            $query = User::with(['wilayah']);

            // ADD HERE: Apply level_wilayah filter
            if (!empty($validated['level_wilayah'])) {
                switch ($validated['level_wilayah']) {
                    case 'semua':
                        // No wilayah filter (all users)
                        break;
                    case 'semua-provinsi':
                        $query->whereHas('wilayah', function ($q) {
                            $q->where('flag', 2);
                        });
                        break;
                    case 'semua-kabkot':
                        $query->whereHas('wilayah', function ($q) {
                            $q->where('flag', 3);
                        });
                        break;
                    case 'provinsi':
                        if (!empty($validated['kd_wilayah'])) {
                            $query->where('kd_wilayah', $validated['kd_wilayah'])
                                ->whereHas('wilayah', function ($q) {
                                    $q->where('flag', 2);
                                });
                        }
                        break;
                    case 'kabkot':
                        if (!empty($validated['kd_wilayah'])) {
                            $query->where('kd_wilayah', $validated['kd_wilayah'])
                                ->whereHas('wilayah', function ($q) {
                                    $q->where('flag', 3);
                                });
                        }
                        break;
                    case 'pusat':
                        $query->where('kd_wilayah', '0');
                        break;
                }
            }

            // Apply search filter
            if (!empty($validated['search'])) {
                $query->where(function ($q) use ($validated) {
                    $q->where('username', 'like', '%' . $validated['search'] . '%')
                        ->orWhere('nama_lengkap', 'like', '%' . $validated['search'] . '%');
                });
            }

            // Apply sorting by nama_lengkap in ascending order
            $query->orderBy('nama_lengkap', 'asc');

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
        } catch (\Exception $e) {
            // Log::error('apiUsers error: ' . $e->getMessage());
            return $errorResponse('An unexpected error occurred: ' . $e->getMessage(), 500);
        }
    }
}
