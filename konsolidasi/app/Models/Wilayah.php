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
    protected $fillable = ['kd_wilayah', 'nama_wilayah', 'flag', 'parent_kd'];

    public function parent()
    {
        return $this->belongsTo(Wilayah::class, 'parent_kd', 'kd_wilayah');
    }

    public function children()
    {
        return $this->hasMany(Wilayah::class, 'parent_kd', 'kd_wilayah');
    }
}
