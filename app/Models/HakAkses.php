<?php

namespace App\Models;

use Spatie\Permission\Models\Permission as SpatiePermission;

class HakAkses extends SpatiePermission
{
    public $timestamps = false;

    protected $fillable = ['name', 'guard_name', 'nama'];

    /** Kode hak akses, mis. anggaran.edit */
    public function getKodeAttribute(): string
    {
        return $this->name;
    }
}
