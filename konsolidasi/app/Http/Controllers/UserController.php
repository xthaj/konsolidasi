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
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
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

    /**
     * Check if the authenticated user has region-based access to modify a target user.
     *
     * @param User $targetUser
     * @param Request $request
     * @return void
     * @throws AuthorizationException
     */
    protected function checkRegionAccess(User $targetUser, Request $request)
    {
        // Get authenticated user
        $authUser = Auth::user();
        if (!$authUser) {
            throw new AuthorizationException('User tidak ditemukan atau belum login.', 401);
        }

        // Region restriction logic
        if (!$authUser->isPusat()) {
            $userKdWilayah = $authUser->kd_wilayah;
            $targetKdWilayah = $targetUser->kd_wilayah;
            $kd_wilayah = $request->input('kd_wilayah', $targetKdWilayah); // Default to target user's kd_wilayah

            // Region checks for provinsi users
            if ($authUser->isProvinsi()) {
                if ($kd_wilayah !== '0' && $kd_wilayah !== $userKdWilayah) {
                    $wilayahData = Cache::get('all_wilayah_data');
                    $wilayah = $wilayahData->firstWhere('kd_wilayah', $kd_wilayah);
                    if (!$wilayah || $wilayah->parent_kd !== $userKdWilayah) {
                        throw new AuthorizationException('Akses dibatasi untuk provinsi atau kabupaten/kota di wilayah Anda.');
                    }
                }
            // Region checks for kabkot users
            } elseif ($authUser->isKabkot()) {
                if ($kd_wilayah !== $userKdWilayah || $targetKdWilayah !== $userKdWilayah) {
                    throw new AuthorizationException('Akses dibatasi untuk kabupaten/kota Anda.');
                }
            }
        }
    }

    public function edit(Request $request, $id)
    {
        try {
            // Find the target user
            $targetUser = User::findOrFail($id);

            // Check region access
            $this->checkRegionAccess($targetUser, $request);

            // Call UserService to update user
            $result = $this->userService->updateUser($id, $request, true);

            if (!$result['success']) {
                throw new ValidationException(Validator::make($request->all(), [], [], $result['errors']));
            }

            return response()->json([
                'message' => $result['message'],
                'data' => null,
            ], 200);
        } catch (ModelNotFoundException $e) {
            // Handle user not found
            return response()->json([
                'message' => 'User tidak ditemukan.',
                'data' => null,
            ], 404);
        } catch (AuthorizationException $e) {
            // Handle authorization failure
            return response()->json([
                'message' => $e->getMessage(),
                'data' => null,
            ], $e->getCode() ?: 403);
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
        try {
            // Find the target user
            $targetUser = User::findOrFail($user_id);
            
            // Check region access
            $this->checkRegionAccess($targetUser, request());

            $cacheKey = 'user_' . $user_id;
            if (Cache::has($cacheKey)) {
                Cache::forget($cacheKey);
                Log::info('Removed user cache', ['user_id' => $user_id, 'cache_key' => $cacheKey]);
            }

            Log::info('Deleting user', [
                'user_id' => $user_id,
                'nama_lengkap' => $targetUser->nama_lengkap
            ]);

            $targetUser->delete();

            return response()->json(['message' => 'User berhasil dihapus']);
        } catch (AuthorizationException $e) {
            // Handle authorization failure
            return response()->json([
                'message' => $e->getMessage(),
                'data' => null,
            ], $e->getCode() ?: 403);
        } catch (\Exception $e) {
            // Handle other exceptions
            // Log::error('Delete user error: ' . $e->getMessage());
            return response()->json([
                'message' => 'An unexpected error occurred: ' . $e->getMessage(),
                'data' => null,
            ], $e->getCode() ?: 500);
        }
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

            // Apply level_wilayah filter
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
