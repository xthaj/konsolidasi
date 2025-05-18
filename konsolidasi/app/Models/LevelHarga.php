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

    public static function getLevelHargaNameComplete(int|string|null $levelharga): ?string
    {
        if (is_null($levelharga)) {
            return null;
        }

        return self::query()
            ->where('kd_level', $levelharga)
            ->value('nama_level');
    }

    public static function getLevelHargaNameShortened(int|string $levelharga): ?string
    {
        $levelNames = [
            '01' => 'HK',
            '02' => 'HKDesa',
            '03' => 'HPB',
            '04' => 'HPed',
            '05' => 'HP',
        ];

        $key = str_pad((string) $levelharga, 2, '0', STR_PAD_LEFT);

        return $levelNames[$key] ?? null;
    }
}
