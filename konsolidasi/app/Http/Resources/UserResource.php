<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        return [
            'user_id' => $this->user_id,
            'nama_wilayah' => $this->wilayah->nama_wilayah === 'NASIONAL' ? 'PUSAT' : $this->wilayah->nama_wilayah,
            'username' => $this->username,
            'nama_lengkap' => $this->nama_lengkap,
            'level' => $this->level_nama,
            'kd_level' => $this->level,
        ];
    }
}
