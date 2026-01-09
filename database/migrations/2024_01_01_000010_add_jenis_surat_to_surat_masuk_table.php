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
        Schema::table('surat_masuk', function (Blueprint $table) {
            if (!Schema::hasColumn('surat_masuk', 'jenis_surat')) {
                $table->enum('jenis_surat', ['pemberitahuan', 'permohonan', 'laporan', 'undangan', 'keputusan', 'instruksi', 'lainnya'])
                    ->default('lainnya')
                    ->after('perihal');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('surat_masuk', function (Blueprint $table) {
            if (Schema::hasColumn('surat_masuk', 'jenis_surat')) {
                $table->dropColumn('jenis_surat');
            }
        });
    }
};
