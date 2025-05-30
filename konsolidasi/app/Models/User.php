<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'user';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'user_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'username',
        'password',
        'nama_lengkap',
        'level',
        'kd_wilayah',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'created_at',
        'updated_at',
    ];

    /**
     * Get the wilayah associated with the user.
     */
    public function wilayah()
    {
        return $this->belongsTo(Wilayah::class, 'kd_wilayah', 'kd_wilayah');
    }

    /**
     * Get the name of the unique identifier for authentication.
     *
     * @return string
     */
    public function getAuthIdentifierName()
    {
        return 'username';
    }

    /**
     * Check if the user is at the pusat level (Admin or Operator).
     *
     * @return bool
     */
    public function isPusat(): bool
    {
        return in_array($this->level, [0, 1]);
    }

    /**
     * Check if the user is at the provinsi level (Admin or Operator).
     *
     * @return bool
     */
    public function isProvinsi(): bool
    {
        return in_array($this->level, [2, 3]);
    }

    /**
     * Check if the user is at the kabkot level (Admin or Operator).
     *
     * @return bool
     */
    public function isKabkot(): bool
    {
        return in_array($this->level, [4, 5]);
    }

    /**
     * Check if the user is an admin (Pusat, Provinsi, or Kabkot).
     *
     * @return bool
     */
    public function isAdmin(): bool
    {
        return in_array($this->level, [0, 2, 4]);
    }

    /**
     * Check if the user is an operator (Pusat, Provinsi, or Kabkot).
     *
     * @return bool
     */
    public function isOperator(): bool
    {
        return in_array($this->level, [1, 3, 5]);
    }

    /**
     * Get the display name for the user's level.
     *
     * @return string
     */
    public function getLevelNamaAttribute(): string
    {
        return match ($this->level) {
            0 => 'Admin Pusat',
            1 => 'Operator Pusat',
            2 => 'Admin Provinsi',
            3 => 'Operator Provinsi',
            4 => 'Admin Kabupaten/Kota',
            5 => 'Operator Kabupaten/Kota',
        };
    }

    /**
     * Check if a username already exists.
     *
     * @param string $username
     * @return bool
     */
    public static function usernameExists(string $username): bool
    {
        return self::where('username', $username)->exists();
    }

    /**
     * Set the username attribute, converting to lowercase and trimming.
     *
     * @param string $value
     */
    public function setUsernameAttribute(string $value): void
    {
        $this->attributes['username'] = Str::lower(trim($value));
    }

    /**
     * Get the display name of the user's wilayah.
     * If wilayah name is "NASIONAL", return "PUSAT" instead.
     *
     * @return string
     */
    public function getWilayahNamaDisplayAttribute(): string
    {
        if (!$this->wilayah) {
            return '-'; // or return null if you prefer
        }

        return Str::lower($this->wilayah->nama_wilayah) === 'nasional'
            ? 'Pusat'
            : $this->wilayah->nama_wilayah;
    }

    protected $casts = [
        'level' => 'integer',
    ];
}
