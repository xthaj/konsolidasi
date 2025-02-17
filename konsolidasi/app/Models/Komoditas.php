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
    protected $keyType = 'string';
    protected $fillable = ['kd_komoditas', 'nama_komoditas'];

    public function inflasi()
    {
        return $this->hasMany(Inflasi::class);
    }
}
