<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Anggaran extends Model
{
    protected $table = 'anggaran';

    public const CREATED_AT = 'dibuat_pada';
    public const UPDATED_AT = 'diubah_pada';

    protected $fillable = ['tahap_id', 'skpd_id', 'rekening_id', 'nilai', 'volume'];

    protected $casts = ['nilai' => 'decimal:2', 'volume' => 'decimal:2'];

    public function tahap(): BelongsTo
    {
        return $this->belongsTo(Tahap::class);
    }

    public function skpd(): BelongsTo
    {
        return $this->belongsTo(Skpd::class);
    }

    public function rekening(): BelongsTo
    {
        return $this->belongsTo(Rekening::class);
    }

    public function kebutuhan(): HasMany
    {
        return $this->hasMany(AnggaranKebutuhan::class);
    }

    public function kebutuhanTambahan(): HasMany
    {
        return $this->hasMany(AnggaranKebutuhanTambahan::class);
    }
}
