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
}
