<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Wilayah extends Model
{
    use HasFactory;

    protected $table = 'wilayah';
    protected $primaryKey = 'kd_wilayah';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;
    protected $fillable = ['kd_wilayah', 'nama_wilayah', 'flag', 'parent_kd', 'inflasi_tracked'];

    public function parent()
    {
        return $this->belongsTo(Wilayah::class, 'parent_kd', 'kd_wilayah');
    }

    public function children()
    {
        return $this->hasMany(Wilayah::class, 'parent_kd', 'kd_wilayah');
    }

    public static function getWilayahName(?string $kdWilayah): ?string
    {
        if (is_null($kdWilayah)) {
            return null;
        }
        return self::query()
            ->where('kd_wilayah', $kdWilayah)
            ->value('nama_wilayah');
    }
}
