<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PengisianRekonsiliasiResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        return [
            'rekonsiliasi_id' => $this->rekonsiliasi_id,
            'kd_komoditas' => $this->inflasi->kd_komoditas,
            'nama_komoditas' => $this->inflasi->komoditas->nama_komoditas,
            'kd_wilayah' => $this->inflasi->kd_wilayah,
            'nama_wilayah' => $this->inflasi->wilayah->nama_wilayah,
            'kd_level' => $this->inflasi->kd_level,
            'nilai_inflasi' => $this->inflasi->nilai_inflasi !== null ? number_format($this->inflasi->nilai_inflasi, 2, '.', '') : '-',
            'user_id' => $this->user_id,
            'alasan' => $this->alasan,
            'detail' => $this->detail,
            'sumber' => $this->media,
            'editor_name' => $this->user ? $this->user->nama_lengkap : null,
        ];
    }
}
