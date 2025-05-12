<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BulanTahunResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'bt_aktif' => [
                'bulan_tahun_id' => $this['bt_aktif']?->bulan_tahun_id,
                'bulan' => $this['bt_aktif']?->bulan,
                'tahun' => $this['bt_aktif']?->tahun,
            ],
            'tahun' => $this['tahun'],
        ];
    }
}
