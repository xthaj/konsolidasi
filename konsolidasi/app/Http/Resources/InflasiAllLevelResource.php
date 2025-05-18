<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InflasiAllLevelResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'kd_komoditas' => $this->kd_komoditas,
            'nama_komoditas' => $this->nama_komoditas,
            'inflasi_01' => $this->inflasi_01 !== null ? number_format($this->inflasi_01, 2, '.', '') : '-',
            'andil_01' => $this->andil_01 !== null ? number_format($this->andil_01, 4, '.', '') : '-',
            'inflasi_02' => $this->inflasi_02 !== null ? number_format($this->inflasi_02, 2, '.', '') : '-',
            'andil_02' => $this->andil_02 !== null ? number_format($this->andil_02, 4, '.', '') : '-',
            'inflasi_03' => $this->inflasi_03 !== null ? number_format($this->inflasi_03, 2, '.', '') : '-',
            'andil_03' => $this->andil_03 !== null ? number_format($this->andil_03, 4, '.', '') : '-',
            'inflasi_04' => $this->inflasi_04 !== null ? number_format($this->inflasi_04, 2, '.', '') : '-',
            'andil_04' => $this->andil_04 !== null ? number_format($this->andil_04, 4, '.', '') : '-',
            'inflasi_05' => $this->inflasi_05 !== null ? number_format($this->inflasi_05, 2, '.', '') : '-',
            'andil_05' => $this->andil_05 !== null ? number_format($this->andil_05, 4, '.', '') : '-',
        ];
    }
}
