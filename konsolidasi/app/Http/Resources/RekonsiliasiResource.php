<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RekonsiliasiResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'rekonsiliasi_id' => $this->rekonsiliasi_id,
            'inflasi' => [
                'inflasi_id' => $this->inflasi->inflasi_id,
                'kd_wilayah' => $this->inflasi->kd_wilayah,
                'nama_wilayah' => $this->inflasi->wilayah ? ucwords(strtolower($this->inflasi->wilayah->nama_wilayah)) : 'Tidak Dikenal',
                'kd_komoditas' => $this->inflasi->kd_komoditas,
                'nama_komoditas' => $this->inflasi->komoditas->nama_komoditas ?? 'N/A',
                'kd_level' => $this->inflasi->kd_level,
                'inflasi' => $this->inflasi->inflasi,
                'inflasi_opposite' => $this->inflasi->inflasi_opposite ?? null,
            ],
            'alasan' => $this->alasan,
            'detail' => $this->detail,
            'media' => $this->media,
            'terakhir_diedit' => $this->terakhir_diedit,
            'user' => $this->user ? [
                'user_id' => $this->user->user_id,
                'nama_lengkap' => $this->user->nama_lengkap,
            ] : null,
        ];
    }
}
