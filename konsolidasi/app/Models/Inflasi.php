<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inflasi extends Model
{
    use HasFactory;

    protected $table = 'inflasi';
    protected $primaryKey = 'inflasi_id';
    protected $fillable = ['kd_komoditas', 'kd_wilayah', 'bulan_tahun_id', 'kd_level', 'harga', 'flag'];

    public function komoditas()
    {
        return $this->belongsTo(Komoditas::class, 'kd_komoditas', 'kd_komoditas');
    }

    public function wilayah()
    {
        return $this->belongsTo(Wilayah::class, 'kd_wilayah', 'kd_wilayah');
    }

    public function bulanTahun()
    {
        return $this->belongsTo(BulanTahun::class, 'bulan_tahun_id', 'bulan_tahun_id');
    }

    public function levelHarga()
    {
        return $this->belongsTo(LevelHarga::class, 'kd_level', 'kd_level');
    }
}
