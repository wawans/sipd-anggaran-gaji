<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rekening extends Model
{
    protected $table = 'rekening';

    public const CREATED_AT = 'dibuat_pada';
    public const UPDATED_AT = 'diubah_pada';

    protected $fillable = ['kode', 'nama', 'parent_rekening_id', 'urutan', 'aktif'];

    protected $casts = ['aktif' => 'boolean', 'urutan' => 'integer', 'tingkat' => 'integer'];

    protected $appends = ['induk_tampil'];

    public function anggaran(): HasMany
    {
        return $this->hasMany(Anggaran::class);
    }

    public function realisasi(): HasMany
    {
        return $this->hasMany(Realisasi::class);
    }

    /** Rekening induk (null = top-level). */
    public function induk(): BelongsTo
    {
        return $this->belongsTo(Rekening::class, 'parent_rekening_id');
    }

    /** Sub-rekening langsung. */
    public function anak(): HasMany
    {
        return $this->hasMany(Rekening::class, 'parent_rekening_id');
    }

    public function getIndukTampilAttribute(): string
    {
        return $this->induk ? $this->induk->kode.' - '.$this->induk->nama : '-';
    }
}
