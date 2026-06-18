<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KomoditasHargaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'kd_komoditas' => $this->kd_komoditas,
            'nama_komoditas' => $this->nama_komoditas,
            'is_harga' => $this->is_harga ?? false,
        ];
    }
}
