<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InflasiResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        return [
            'kd_komoditas' => $this->kd_komoditas,
            'nama_komoditas' => $this->nama_komoditas,
            'inflasi_id' => $this->inflasi_id ?? null,
            'nilai_inflasi' => $this->nilai_inflasi !== null ? number_format($this->nilai_inflasi, 2, '.', '') : '-',
            'andil' => $this->andil !== null ? number_format($this->andil, 4, '.', '') : '-',
            'kd_wilayah' => $this->kd_wilayah ?? null,
        ];
    }
}
