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
        Schema::table('surat_keluar', function (Blueprint $table) {
            $table->string('share_token')->unique()->nullable()->after('tanggal_pengiriman');
            $table->boolean('is_shared')->default(false)->after('share_token');
            $table->timestamp('shared_at')->nullable()->after('is_shared');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('surat_keluar', function (Blueprint $table) {
            $table->dropColumn(['share_token', 'is_shared', 'shared_at']);
        });
    }
};
