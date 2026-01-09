<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations - Add foreign key constraints after all tables are created
     */
    public function up(): void
    {
        // Add constraint for unit_kerja.kepala_unit_id -> users.id
        Schema::table('unit_kerja', function (Blueprint $table) {
            $table->foreign('kepala_unit_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });

        // Add constraint for surat_masuk.user_id -> users.id
        Schema::table('surat_masuk', function (Blueprint $table) {
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });

        // Add constraint for surat_keluar.user_id -> users.id
        Schema::table('surat_keluar', function (Blueprint $table) {
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });

        // Add constraint for disposisi.diteruskan_ke_id -> users.id
        Schema::table('disposisi', function (Blueprint $table) {
            $table->foreign('diteruskan_ke_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });

        // Add constraint for riwayat_disposisi.user_id -> users.id
        Schema::table('riwayat_disposisi', function (Blueprint $table) {
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('unit_kerja', function (Blueprint $table) {
            $table->dropForeign(['kepala_unit_id']);
        });

        Schema::table('surat_masuk', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        Schema::table('surat_keluar', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        Schema::table('disposisi', function (Blueprint $table) {
            $table->dropForeign(['diteruskan_ke_id']);
        });

        Schema::table('riwayat_disposisi', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });
    }
};
