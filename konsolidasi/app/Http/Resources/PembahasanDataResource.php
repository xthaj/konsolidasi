<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PembahasanDataResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $inflasi = $this->inflasi;
        $kdLevel = $request->input('kd_level', '01');

        // Helper to convert inflation value to status (Naik, Stabil, Turun)
        $toStatus = fn($value) => $value === null ? '-' : ($value > 0 ? 'Naik' : ($value == 0 ? 'Stabil' : 'Turun'));

        return [
            'rekonsiliasi_id' => $this->rekonsiliasi_id,
            'kd_wilayah' => $inflasi->kd_wilayah,
            'nama_wilayah' => $inflasi->kd_wilayah . ' - ' . ($inflasi->wilayah ? strtoupper($inflasi->wilayah->nama_wilayah) : 'Tidak Dikenal'),
            'kd_komoditas' => $inflasi->kd_komoditas,
            'nama_komoditas' => $inflasi->komoditas->nama_komoditas ?? 'N/A',
            'kd_level' => $inflasi->kd_level,
            'inflasi_kota' => in_array($kdLevel, ['01', '02'])
                ? ($this->inflasi_kota !== null ? number_format($this->inflasi_kota, 2, '.', '') : '-')
                : null,
            'inflasi_desa' => in_array($kdLevel, ['01', '02'])
                ? $toStatus($this->inflasi_desa)
                : null,
            'nilai_inflasi' => $inflasi->nilai_inflasi !== null
                ? number_format($inflasi->nilai_inflasi, 2, '.', '')
                : '-',
            'alasan' => $this->alasan,
            'detail' => $this->detail ?? '-',
            'sumber' => $this->media,
            'editor_name' => $this->user ? $this->user->nama_lengkap : null,
            'pembahasan' => $this->pembahasan,
        ];
    }
}
