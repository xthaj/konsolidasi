<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    public function getProvinsi(): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'kd_wilayah' => $user->kd_wilayah,
                'is_provinsi' => $user->isProvinsi(),
                'wilayah_level' => $user->isProvinsi() ? 'provinsi' : 'kabkot',
            ],
        ]);
    }
}
