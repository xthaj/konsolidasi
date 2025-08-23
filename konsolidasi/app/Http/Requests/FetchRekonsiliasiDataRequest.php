<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Auth\Access\AuthorizationException;

class FetchRekonsiliasiDataRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        if (!Auth::check()) {
            return false;
        }
        $user = Auth::user();

        $userKdWilayah = $user->kd_wilayah;
        $level_wilayah = $this->input('level_wilayah');
        $kd_wilayah = $this->input('kd_wilayah');

        // Region restriction logic from restrictAccessByRegion
        if (!$user->isPusat()) {
            // Restrict non-pusat users to active BulanTahun
            $activeBulanTahun = Cache::get('bt_aktif')['bt_aktif'] ?? throw new AuthorizationException('Tidak ada periode aktif.');
            if ($this->input('bulan') != $activeBulanTahun->bulan || $this->input('tahun') != $activeBulanTahun->tahun) {
                throw new AuthorizationException('Akses hanya untuk periode aktif.');
            }

            if ($user->isProvinsi()) {
                if ($level_wilayah === 'semua-provinsi') {
                    throw new AuthorizationException('Akses semua provinsi tidak diizinkan untuk pengguna provinsi.');
                }
                if ($kd_wilayah !== '0' && $kd_wilayah !== $userKdWilayah) {
                    $wilayahData = Cache::get('all_wilayah_data');
                    $wilayah = $wilayahData->firstWhere('kd_wilayah', $kd_wilayah);
                    if (!$wilayah || $wilayah->parent_kd !== $userKdWilayah) {
                        throw new AuthorizationException('Akses dibatasi untuk provinsi atau kabupaten/kota di wilayah Anda.');
                    }
                }
            } elseif ($user->isKabkot()) {
                if ($level_wilayah !== 'kabkot') {
                    throw new AuthorizationException('Akses dibatasi untuk kabupaten/kota Anda.');
                }
                if ($kd_wilayah !== $userKdWilayah) {
                    throw new AuthorizationException('Akses dibatasi untuk kabupaten/kota Anda.');
                }
            }
        }

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'bulan' => 'nullable|integer|between:1,12',
            'tahun' => 'nullable|integer|between:2000,2100',
            'kd_level' => 'nullable|in:00,01,02,03,04,05',
            'level_wilayah' => 'nullable|in:semua,semua-provinsi,semua-kabkot,provinsi,kabkot,provinsi-kabkot',
            'kd_wilayah' => 'nullable|string|max:4',
            'status_rekon' => 'nullable|in:00,01,02',
            'kd_komoditas' => 'nullable|string|max:10',
            'user_id' => 'nullable|exists:user,user_id',
        ];
    }

    public function messages()
    {
        return [
            'bulan.required' => 'Bulan harus diisi.',
            'kd_wilayah.required' => 'Kode wilayah harus diisi.',
            'kd_level.in' => 'Kode level tidak valid.',
        ];
    }

    protected function failedAuthorization()
    {
        throw new AuthorizationException('Akses tidak diizinkan.', 403, null, [
            'message' => 'Akses tidak diizinkan.',
            'data' => ['rekonsiliasi' => null, 'title' => 'Rekonsiliasi'],
        ]);
    }
}
