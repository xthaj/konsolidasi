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
            // Validate only inflasi_id and nilai_inflasi as required, andil as optional
            $validator = Validator::make($request->all(), [
                'nilai_inflasi' => 'required|numeric',
                'andil' => 'nullable|numeric',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors()->first(),
                ], 422);
            }

            DB::beginTransaction();

            // Find the Inflasi record by inflasi_id
            $inflasi = Inflasi::findOrFail($id);

            // Prepare data to update (only nilai_inflasi and andil if provided)
            $updateData = [
                'nilai_inflasi' => $request->nilai_inflasi,
            ];

            // Only include andil if it exists in the request and kd_wilayah is '0'
            if ($request->has('andil')) {
                $updateData['andil'] = $request->andil;
            }

            // Update the record
            $inflasi->update($updateData);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Data inflasi berhasil diperbarui',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memperbarui data: ' . $e->getMessage(),
            ], 500);
        }
    }
}
