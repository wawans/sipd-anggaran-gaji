<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnggaranKebutuhanTambahan extends Model
{
    protected $table = 'anggaran_kebutuhan_tambahan';

    public $timestamps = false;

    protected $fillable = ['anggaran_id', 'jenis_kebutuhan_tambahan_id', 'nominal'];

    protected $casts = ['nominal' => 'decimal:2'];

    public function anggaran(): BelongsTo
    {
        return $this->belongsTo(Anggaran::class);
    }

    public function jenisKebutuhanTambahan(): BelongsTo
    {
        return $this->belongsTo(JenisKebutuhanTambahan::class);
    }
}
