<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('riwayat_disposisi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('disposisi_id')->constrained('disposisi')->cascadeOnDelete();
            $table->unsignedBigInteger('user_id'); // FK constraint added in migration 000009
            $table->enum('aksi', ['diteruskan', 'dikembalikan', 'diselesaikan'])->default('diteruskan');
            $table->text('keterangan')->nullable();
            $table->timestamp('waktu_aksi');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('riwayat_disposisi');
    }
};
