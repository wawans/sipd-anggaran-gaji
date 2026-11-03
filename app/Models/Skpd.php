<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Skpd extends Model
{
    protected $table = 'skpd';

    public const CREATED_AT = 'dibuat_pada';
    public const UPDATED_AT = 'diubah_pada';

    protected $fillable = ['kode', 'nama', 'aktif'];

    protected $casts = ['aktif' => 'boolean'];

    public function anggaran(): HasMany
    {
        return $this->hasMany(Anggaran::class);
    }

    public function realisasi(): HasMany
    {
        return $this->hasMany(Realisasi::class);
    }
}
