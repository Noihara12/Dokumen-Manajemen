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
        Schema::create('backups_dokumen_manajemen', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('backup_type'); // 'database', 'files', 'full'
            $table->string('file_path')->nullable();
            $table->string('file_name');
            $table->unsignedBigInteger('file_size')->default(0); // dalam bytes
            $table->string('status')->default('completed'); // 'pending', 'processing', 'completed', 'failed'
            $table->text('notes')->nullable();
            $table->string('backup_frequency')->nullable(); // 'manual', 'daily', 'weekly', 'monthly'
            $table->dateTime('backup_date');
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index('status');
            $table->index('backup_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('backups_dokumen_manajemen');
    }
};
