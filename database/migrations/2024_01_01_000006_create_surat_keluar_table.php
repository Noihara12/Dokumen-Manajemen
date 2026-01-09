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
        Schema::create('surat_keluar', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_surat')->unique();
            $table->date('tanggal_surat');
            $table->string('tujuan');
            $table->text('perihal');
            $table->foreignId('klasifikasi_surat_id')->constrained('klasifikasi_surat');
            $table->foreignId('unit_kerja_id')->nullable()->constrained('unit_kerja');
            $table->unsignedBigInteger('user_id'); // FK constraint added in migration 000009
            $table->text('catatan')->nullable();
            $table->string('file_surat')->nullable();
            $table->integer('jumlah_lampiran')->default(0);
            $table->enum('status', ['draft', 'dikirim', 'arsip'])->default('draft');
            $table->timestamp('tanggal_pengiriman')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('surat_keluar');
    }
};
