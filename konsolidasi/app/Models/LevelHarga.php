<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class LevelHarga extends Model
{
    use HasFactory;

    protected $table = 'level_harga';
    protected $primaryKey = 'kd_level';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['kd_level', 'nama_level'];

    public function inflasi()
    {
        return $this->hasMany(Inflasi::class);
    }

    public static function getLevelHargaNameComplete(int|string $levelharga): ?string
    {
        $levelNames = [
            '01' => 'Harga Konsumen Kota',
            '02' => 'Harga Konsumen Desa',
            '03' => 'Harga Perdagangan Besar',
            '04' => 'Harga Produsen Desa',
            '05' => 'Harga Produsen',
        ];

        $key = str_pad((string) $levelharga, 2, '0', STR_PAD_LEFT);

        return $levelNames[$key] ?? null;
    }

    public static function getLevelHargaNameShortened(int|string $levelharga): ?string
    {
        $levelNames = [
            '01' => 'HK',
            '02' => 'HK Desa',
            '03' => 'HPB',
            '04' => 'HPed',
            '05' => 'HP',
        ];

        $key = str_pad((string) $levelharga, 2, '0', STR_PAD_LEFT);

        return $levelNames[$key] ?? null;
    }
}
