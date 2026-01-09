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
        // Drop foreign key constraints first
        Schema::table('surat_masuk', function (Blueprint $table) {
            if (Schema::hasColumn('surat_masuk', 'klasifikasi_surat_id')) {
                try {
                    $table->dropForeign('surat_masuk_klasifikasi_surat_id_foreign');
                } catch (\Exception $e) {
                    // Foreign key might not exist
                }
            }
        });

        Schema::table('surat_keluar', function (Blueprint $table) {
            if (Schema::hasColumn('surat_keluar', 'klasifikasi_surat_id')) {
                try {
                    $table->dropForeign('surat_keluar_klasifikasi_surat_id_foreign');
                } catch (\Exception $e) {
                    // Foreign key might not exist
                }
            }
        });

        // Drop unused columns
        Schema::table('surat_masuk', function (Blueprint $table) {
            if (Schema::hasColumn('surat_masuk', 'klasifikasi_surat_id')) {
                $table->dropColumn('klasifikasi_surat_id');
            }
        });

        Schema::table('surat_keluar', function (Blueprint $table) {
            if (Schema::hasColumn('surat_keluar', 'klasifikasi_surat_id')) {
                $table->dropColumn('klasifikasi_surat_id');
            }
        });

        // Drop unused tables
        Schema::dropIfExists('klasifikasi_surat');
        Schema::dropIfExists('unit_kerja');
        Schema::dropIfExists('jabatan');

        // Update surat_masuk table - add required fields
        Schema::table('surat_masuk', function (Blueprint $table) {
            if (!Schema::hasColumn('surat_masuk', 'jenis_surat')) {
                $table->string('jenis_surat')->nullable()->after('perihal');
            }
            if (!Schema::hasColumn('surat_masuk', 'disposisi_ke')) {
                $table->string('disposisi_ke')->nullable()->after('jenis_surat');
            }
            if (!Schema::hasColumn('surat_masuk', 'isi_disposisikan')) {
                $table->text('isi_disposisikan')->nullable()->after('disposisi_ke');
            }
            if (!Schema::hasColumn('surat_masuk', 'status')) {
                $table->enum('status', ['diterima', 'diproses', 'selesai'])->default('diterima')->after('jumlah_lampiran');
            }
        });

        // Update surat_keluar table - add required fields
        Schema::table('surat_keluar', function (Blueprint $table) {
            if (!Schema::hasColumn('surat_keluar', 'status')) {
                $table->enum('status', ['draft', 'dikirim', 'arsip'])->default('draft')->after('jumlah_lampiran');
            }
            if (!Schema::hasColumn('surat_keluar', 'tanggal_pengiriman')) {
                $table->dateTime('tanggal_pengiriman')->nullable()->after('status');
            }
        });

        // Update disposisi table - ensure required fields
        Schema::table('disposisi', function (Blueprint $table) {
            if (!Schema::hasColumn('disposisi', 'status')) {
                $table->enum('status', ['ditugaskan', 'dalam_proses', 'selesai'])->default('ditugaskan')->after('catatan');
            }
        });

        // Update users table - add required fields
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'username')) {
                $table->string('username')->unique()->nullable()->after('email');
            }
        });

        // Add share_token fields if not exist
        if (Schema::hasTable('surat_masuk')) {
            Schema::table('surat_masuk', function (Blueprint $table) {
                if (!Schema::hasColumn('surat_masuk', 'share_token')) {
                    $table->string('share_token')->unique()->nullable()->after('status');
                }
                if (!Schema::hasColumn('surat_masuk', 'is_shared')) {
                    $table->boolean('is_shared')->default(false)->after('share_token');
                }
                if (!Schema::hasColumn('surat_masuk', 'shared_at')) {
                    $table->dateTime('shared_at')->nullable()->after('is_shared');
                }
            });
        }

        if (Schema::hasTable('surat_keluar')) {
            Schema::table('surat_keluar', function (Blueprint $table) {
                if (!Schema::hasColumn('surat_keluar', 'share_token')) {
                    $table->string('share_token')->unique()->nullable()->after('tanggal_pengiriman');
                }
                if (!Schema::hasColumn('surat_keluar', 'is_shared')) {
                    $table->boolean('is_shared')->default(false)->after('share_token');
                }
                if (!Schema::hasColumn('surat_keluar', 'shared_at')) {
                    $table->dateTime('shared_at')->nullable()->after('is_shared');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate dropped tables if needed
        Schema::create('jabatan', function (Blueprint $table) {
            $table->id();
            $table->string('nama_jabatan');
            $table->timestamps();
        });

        Schema::create('unit_kerja', function (Blueprint $table) {
            $table->id();
            $table->string('nama_unit_kerja');
            $table->timestamps();
        });

        Schema::create('klasifikasi_surat', function (Blueprint $table) {
            $table->id();
            $table->string('nama_klasifikasi');
            $table->timestamps();
        });
    }
};
