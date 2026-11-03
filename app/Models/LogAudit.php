<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogAudit extends Model
{
    protected $table = 'log_audit';

    public const CREATED_AT = 'waktu';
    public const UPDATED_AT = null;

    protected $fillable = ['pengguna_id', 'aksi', 'entitas', 'entitas_id', 'keterangan'];

    protected $casts = ['waktu' => 'datetime'];

    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class);
    }
}
