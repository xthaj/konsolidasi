<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AllWilayahResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'kd_wilayah' => $this->kd_wilayah,
            'nama_wilayah' => $this->nama_wilayah,
            'parent_kd' => $this->parent_kd,
            'inflasi_tracked' => (bool) $this->inflasi_tracked,
        ];
    }
}
