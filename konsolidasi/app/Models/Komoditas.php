<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Komoditas extends Model
{
    use HasFactory;

    protected $table = 'komoditas';
    protected $primaryKey = 'kd_komoditas';
    public $incrementing = false;
    protected $keyType = 'int';
    protected $fillable = ['kd_komoditas', 'nama_komoditas'];

    protected $casts = [
        'kd_komoditas' => 'integer',
        'nama_komoditas' => 'string'
    ];

    public function inflasi()
    {
        return $this->hasMany(Inflasi::class);
    }

    public static function getKomoditasName(?string $kdKomoditas): ?string
    {
        if (is_null($kdKomoditas)) {
            return null;
        }
        return self::query()
            ->where('kd_komoditas', $kdKomoditas)
            ->value('nama_komoditas');
    }
}
