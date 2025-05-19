<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PembahasanDataResource extends JsonResource
{
    public function toArray($request)
    {
        $inflasi = $this->inflasi;
        $kdLevel = $request->input('kd_level', '01');

        $toStatus = function ($value) {
            if ($value === null) return '-';
            return $value > 0 ? 'naik' : ($value == 0 ? 'Stabil' : 'Turun');
        };

        return [
            'rekonsiliasi_id' => $this->rekonsiliasi_id,
            'kd_wilayah' => $inflasi->kd_wilayah,
            'nama_wilayah' => $inflasi->wilayah ? strtoupper($inflasi->wilayah->nama_wilayah) : 'Tidak Dikenal',
            'kd_komoditas' => $inflasi->kd_komoditas,
            'nama_komoditas' => $inflasi->komoditas->nama_komoditas ?? 'N/A',
            'kd_level' => $inflasi->kd_level,
            'inflasi_kota' => in_array($kdLevel, ['01', '02']) ? ($this->inflasi_kota ?? '-') : null,
            'inflasi_desa' => in_array($kdLevel, ['01', '02']) ? ($this->inflasi_desa !== null ? $toStatus($this->inflasi_desa) : '-') : null,
            'nilai_inflasi' => $inflasi->inflasi,
            'alasan' => $this->alasan,
            'detail' => $this->detail ?? '-',
            'sumber' => $this->media,
            'editor_name' => $this->user ? $this->user->nama_lengkap : null,
            'pembahasan' => $this->pembahasan,
        ];
    }
}
