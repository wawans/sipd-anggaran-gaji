<?php

namespace App\Traits;

use App\Models\LogAudit;
use Illuminate\Support\Facades\Auth;

trait MencatatAudit
{
    protected function catatAudit(string $aksi, string $entitas, ?int $entitasId = null, ?string $keterangan = null): void
    {
        LogAudit::create([
            'pengguna_id' => Auth::id(),
            'aksi' => $aksi,
            'entitas' => $entitas,
            'entitas_id' => $entitasId,
            'keterangan' => $keterangan,
        ]);
    }
}
