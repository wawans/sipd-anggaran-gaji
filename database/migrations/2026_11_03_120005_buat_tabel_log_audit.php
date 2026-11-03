<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('log_audit', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengguna_id')->nullable()->constrained('pengguna');
            $table->timestampTz('waktu')->useCurrent();
            $table->string('aksi', 50);
            $table->string('entitas', 50);
            $table->bigInteger('entitas_id')->nullable();
            $table->text('keterangan')->nullable();
            $table->index('waktu', 'idx_log_audit_waktu');
            $table->index('pengguna_id', 'idx_log_audit_pengguna');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('log_audit');
    }
};
