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
        // Recreate unit_kerja table
        if (!Schema::hasTable('unit_kerja')) {
            Schema::create('unit_kerja', function (Blueprint $table) {
                $table->id();
                $table->string('kode_unit')->unique();
                $table->string('nama_unit');
                $table->text('deskripsi')->nullable();
                $table->unsignedBigInteger('kepala_unit_id')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // Add unit_kerja_id to users table
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'unit_kerja_id')) {
                $table->unsignedBigInteger('unit_kerja_id')->nullable()->after('role_id');
                $table->foreign('unit_kerja_id')->references('id')->on('unit_kerja')->cascadeOnDelete();
            }
        });

        // Update disposisi table to reference unit_kerja instead of text
        Schema::table('disposisi', function (Blueprint $table) {
            if (!Schema::hasColumn('disposisi', 'unit_kerja_id')) {
                $table->unsignedBigInteger('unit_kerja_id')->nullable()->after('surat_masuk_id');
                $table->foreign('unit_kerja_id')->references('id')->on('unit_kerja')->cascadeOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('disposisi', function (Blueprint $table) {
            if (Schema::hasColumn('disposisi', 'unit_kerja_id')) {
                $table->dropForeign(['unit_kerja_id']);
                $table->dropColumn('unit_kerja_id');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'unit_kerja_id')) {
                $table->dropForeign(['unit_kerja_id']);
                $table->dropColumn('unit_kerja_id');
            }
        });

        Schema::dropIfExists('unit_kerja');
    }
};
