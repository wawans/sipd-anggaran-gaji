<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JenisKebutuhanTambahan extends Model
{
    protected $table = 'jenis_kebutuhan_tambahan';

    public $timestamps = false;

    protected $fillable = ['nama', 'urutan', 'aktif'];

    protected $casts = ['aktif' => 'boolean', 'urutan' => 'integer'];
}
