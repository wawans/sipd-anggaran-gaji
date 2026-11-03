<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JenisKebutuhan extends Model
{
    protected $table = 'jenis_kebutuhan';

    public $timestamps = false;

    protected $fillable = ['nama', 'urutan', 'aktif'];

    protected $casts = ['aktif' => 'boolean', 'urutan' => 'integer'];
}
