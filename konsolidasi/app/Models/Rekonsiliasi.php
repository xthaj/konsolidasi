<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Rekonsiliasi extends Model
{
    use HasFactory;

    protected $table = 'rekonsiliasi';
    protected $primaryKey = 'rekonsiliasi_id';
    protected $fillable = ['inflasi_id', 'user_id', 'terakhir_diedit', 'alasan', 'detail', 'media'];
    public function inflasi()
    {
        return $this->belongsTo(Inflasi::class, 'inflasi_id', 'inflasi_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
