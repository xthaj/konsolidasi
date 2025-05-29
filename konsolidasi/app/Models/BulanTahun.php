<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BulanTahun extends Model
{
    use HasFactory;

    protected $table = 'bulan_tahun';
    protected $primaryKey = 'bulan_tahun_id';
    protected $fillable = ['bulan', 'tahun', 'aktif'];
    public $timestamps = false;

    public function inflasi()
    {
        return $this->hasMany(Inflasi::class);
    }

    public static function getBulanName(?int $bulan): ?string
    {
        if (is_null($bulan)) {
            return null;
        }

        $bulanNames = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember'
        ];

        return $bulanNames[$bulan] ?? null;
    }
}
