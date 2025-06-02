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
            'bulan_tahun_id' => $this->bulan_tahun_id,
            'kd_komoditas' => $this->kd_komoditas,
            'nama_komoditas' => $this->nama_komoditas,
            'inflasi_id' => $this->inflasi_id ?? null,
            'nilai_inflasi' => $this->nilai_inflasi !== null ? number_format($this->nilai_inflasi, 2, '.', '') : '-',
            'andil' => $this->andil !== null ? number_format($this->andil, 2, '.', '') : '-',
            'kd_wilayah' => $this->kd_wilayah ?? null,
            'nama_wilayah' => optional($this->wilayah)->nama_wilayah,
        ];
    }
}
