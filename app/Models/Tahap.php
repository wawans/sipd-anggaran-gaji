<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tahap extends Model
{
    protected $table = 'tahap';

    public const CREATED_AT = 'dibuat_pada';
    public const UPDATED_AT = 'diubah_pada';

    protected $fillable = ['tahun', 'nama', 'tgl_mulai', 'tgl_selesai', 'status', 'catatan'];

    protected $casts = [
        'tahun' => 'integer',
        'tgl_mulai' => 'date',
        'tgl_selesai' => 'date',
    ];

    public const STATUS_DRAF = 'draf';
    public const STATUS_FINAL = 'final';
    public const STATUS_TERKUNCI = 'terkunci';

    public function anggaran(): HasMany
    {
        return $this->hasMany(Anggaran::class);
    }

    public function terkunci(): bool
    {
        return $this->status === self::STATUS_TERKUNCI;
    }
}
