<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnggaranKebutuhan extends Model
{
    protected $table = 'anggaran_kebutuhan';

    public $timestamps = false;

    protected $fillable = ['anggaran_id', 'jenis_kebutuhan_id', 'nominal'];

    protected $casts = ['nominal' => 'decimal:2'];

    public function anggaran(): BelongsTo
    {
        return $this->belongsTo(Anggaran::class);
    }

    public function jenisKebutuhan(): BelongsTo
    {
        return $this->belongsTo(JenisKebutuhan::class);
    }
}
