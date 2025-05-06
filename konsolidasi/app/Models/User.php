<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasFactory;

    protected $table = 'user';
    protected $primaryKey = 'user_id';

    protected $fillable = [
        'username',
        'password',
        'nama_lengkap',
        'level',
        'kd_wilayah'
    ];

    protected $hidden = [
        'password',
        'created_at',
        'updated_at'
    ];
    public function wilayah()
    {
        return $this->belongsTo(Wilayah::class, 'kd_wilayah', 'kd_wilayah');
    }

    public function getAuthIdentifierName()
    {
        return 'username'; // Return the name of  username field
    }

    // Helper method
    public function isPusat(): bool
    {
        return in_array($this->level, [0, 1]); // Admin or Operator Pusat
    }

    public static function usernameExists($username)
    {
        return self::where('username', $username)->exists();
    }

    // mutator
    public function setUsernameAttribute($value)
    {
        $this->attributes['username'] = strtolower(trim($value));
    }
}
