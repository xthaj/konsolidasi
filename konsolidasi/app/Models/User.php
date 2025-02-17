<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory;

    protected $table = 'user';
    protected $primaryKey = 'user_id';
    protected $fillable = ['username', 'password', 'nama_lengkap', 'is_pusat', 'kd_wilayah'];

    public function wilayah()
    {
        return $this->belongsTo(Wilayah::class, 'kd_wilayah', 'kd_wilayah');
    }

    public function getAuthIdentifierName()
    {
        return 'username'; // Return the name of your username field (e.g., 'user_identifier')
    }
}
