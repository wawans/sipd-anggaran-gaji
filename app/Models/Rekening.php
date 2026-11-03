<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rekening extends Model
{
    protected $table = 'rekening';

    public const CREATED_AT = 'dibuat_pada';
    public const UPDATED_AT = 'diubah_pada';

    protected $fillable = ['kode', 'nama', 'kelompok', 'urutan', 'aktif'];

    protected $casts = ['aktif' => 'boolean', 'urutan' => 'integer'];

    public function anggaran(): HasMany
    {
        return $this->hasMany(Anggaran::class);
    }

    public function realisasi(): HasMany
    {
        return $this->hasMany(Realisasi::class);
    }
}
