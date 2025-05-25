<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Inflasi;
use Illuminate\Support\Facades\DB;

class InflasiController extends Controller
{
    public function update(Request $request, $id)
    {
        try {
            // Validate request
            $validator = Validator::make($request->all(), [
                'nilai_inflasi' => 'required|numeric',
                'andil' => 'nullable|numeric',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => $validator->errors()->first(),
                ], 422);
            }

            DB::beginTransaction();

            // Find the Inflasi record
            $inflasi = Inflasi::findOrFail($id);

            // Prepare data to update
            $updateData = [
                'nilai_inflasi' => $request->nilai_inflasi,
            ];

            // Include andil only if provided and kd_wilayah is '0'
            if ($request->has('andil') && $inflasi->kd_wilayah === '0') {
                $updateData['andil'] = $request->andil;
            }

            // Update the record
            $inflasi->update($updateData);

            DB::commit();

            return response()->json([
                'message' => 'Data inflasi berhasil diperbarui',
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Data inflasi tidak ditemukan.',
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal memperbarui data: ' . $e->getMessage(),
            ], 500);
        }
    }
}
