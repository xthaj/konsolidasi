<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Alasan extends Model
{
    protected $table = 'alasan';
    protected $primaryKey = 'alasan_id';

    protected $fillable = ['keterangan'];
}
