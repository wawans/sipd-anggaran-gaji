<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Realisasi extends Model
{
    protected $table = 'realisasi';

    public const CREATED_AT = 'diimpor_pada';
    public const UPDATED_AT = null;

    protected $fillable = ['periode', 'skpd_id', 'rekening_id', 'jumlah', 'file_sumber', 'diimpor_oleh'];

    protected $casts = ['jumlah' => 'decimal:2'];

    public function skpd(): BelongsTo
    {
        return $this->belongsTo(Skpd::class);
    }

    public function rekening(): BelongsTo
    {
        return $this->belongsTo(Rekening::class);
    }

    public function pengimpor(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'diimpor_oleh');
    }
}
