<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;

class Pengguna extends Authenticatable
{
    use HasRoles;

    protected $table = 'pengguna';

    public const CREATED_AT = 'dibuat_pada';
    public const UPDATED_AT = 'diubah_pada';

    protected $fillable = ['nama', 'username', 'kata_sandi', 'aktif'];

    protected $hidden = ['kata_sandi'];

    protected $casts = ['aktif' => 'boolean'];

    /** Kolom kata sandi kustom untuk autentikasi Laravel. */
    public function getAuthPassword(): string
    {
        return $this->kata_sandi;
    }

    /** Relasi peran untuk tampilan (spatie memakai roles() secara internal). */
    public function daftarPeran(): BelongsToMany
    {
        return $this->belongsToMany(Peran::class, 'pengguna_peran', 'pengguna_id', 'peran_id');
    }

    public function punyaHakAkses(string $kode): bool
    {
        return $this->hasPermissionTo($kode);
    }
}
