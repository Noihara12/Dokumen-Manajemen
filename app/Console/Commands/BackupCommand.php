<?php

namespace App\Console\Commands;

use App\Models\Backup;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class BackupCommand extends Command
{
    protected $signature = 'backup:run {--type=full : Type of backup (database, files, full)}';
    protected $description = 'Perform automated backup of database and/or files';

    public function handle()
    {
        $this->info('🔄 Starting backup process...');

        try {
            $backupType = $this->option('type');

            $backup = new Backup();
            $backup->user_id = 1; // System/Admin backup
            $backup->backup_type = $backupType;
            $backup->status = 'processing';
            $backup->backup_frequency = 'automatic';
            $backup->backup_date = now();
            $backup->file_name = '';
            $backup->file_path = '';
            $backup->file_size = 0;
            $backup->save();

            $this->performBackup($backup);

            $this->info('✅ Backup completed successfully!');
            $this->info("📦 Backup ID: {$backup->id}");
            $this->info("💾 File size: {$backup->getHumanReadableSize()}");

        } catch (\Exception $e) {
            $this->error('❌ Backup failed: ' . $e->getMessage());
        }
    }

    private function performBackup(Backup $backup)
    {
        try {
            $backupDir = storage_path('backups');
            if (!is_dir($backupDir)) {
                mkdir($backupDir, 0755, true);
            }

            $timestamp = now()->format('Y-m-d_H-i-s');

            if ($backup->backup_type == 'database' || $backup->backup_type == 'full') {
                $this->info('🗂️ Backing up database...');
                $this->backupDatabase($backup, $timestamp);
            }

            if ($backup->backup_type == 'files' || $backup->backup_type == 'full') {
                $this->info('📁 Backing up files...');
                $this->backupFiles($backup, $timestamp);
            }

            $backup->status = 'completed';
            $backup->completed_at = now();
            $backup->save();

            // Cleanup old backups
            $this->info('🗑️ Cleaning up old backups...');
            $this->cleanupOldBackups();

        } catch (\Exception $e) {
            $backup->status = 'failed';
            $backup->notes = $e->getMessage();
            $backup->save();
            throw $e;
        }
    }

    private function backupDatabase(Backup $backup, $timestamp)
    {
        $backupDir = storage_path('backups/database');
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $fileName = "db_backup_{$timestamp}.sql";
        $filePath = $backupDir . '/' . $fileName;

        $host = config('database.connections.mysql.host');
        $user = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');
        $database = config('database.connections.mysql.database');

        $command = "mysqldump -h {$host} -u {$user}";
        if ($password) {
            $command .= " -p{$password}";
        }
        $command .= " {$database} > \"{$filePath}\" 2>&1";

        exec($command);

        if (file_exists($filePath)) {
            $fileSize = filesize($filePath);
            $backup->file_path = "backups/database/{$fileName}";
            $backup->file_name = $fileName;
            $backup->file_size = $fileSize;
            $backup->save();
            $this->info("✓ Database backup created: {$fileName}");
        } else {
            throw new \Exception('Failed to create database backup');
        }
    }

    private function backupFiles(Backup $backup, $timestamp)
    {
        $backupDir = storage_path('backups/files');
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $fileName = "files_backup_{$timestamp}.zip";
        $filePath = $backupDir . '/' . $fileName;

        $zip = new \ZipArchive();
        if ($zip->open($filePath, \ZipArchive::CREATE) !== true) {
            throw new \Exception('Failed to create zip file');
        }

        // Hanya backup file yang reference ada di database (surat masuk & keluar yang active)
        $activeFiles = [];
        
        // Get file dari surat masuk
        $suratMasuk = \DB::select('SELECT file_surat FROM surat_masuk WHERE file_surat IS NOT NULL AND file_surat != ""');
        foreach ($suratMasuk as $surat) {
            if (!empty($surat->file_surat)) {
                $activeFiles[] = $surat->file_surat;
            }
        }
        
        // Get file dari surat keluar
        $suratKeluar = \DB::select('SELECT file_surat FROM surat_keluar WHERE file_surat IS NOT NULL AND file_surat != ""');
        foreach ($suratKeluar as $surat) {
            if (!empty($surat->file_surat)) {
                $activeFiles[] = $surat->file_surat;
            }
        }
        
        // Add hanya active files ke zip dengan struktur folder yang benar
        $storagePath = storage_path('app/public'); // Files disimpan dalam public disk
        foreach ($activeFiles as $fileRef) {
            $fullPath = $storagePath . '/' . $fileRef;
            if (file_exists($fullPath) && is_file($fullPath)) {
                // Preserve folder structure: surat_masuk/xxx.pdf -> surat_masuk/xxx.pdf
                $zip->addFile($fullPath, $fileRef);
            }
        }
        
        $zip->close();

        if (file_exists($filePath) && filesize($filePath) > 0) {
            $fileSize = filesize($filePath);
            $backup->file_path = "backups/files/{$fileName}";
            $backup->file_name = $fileName;
            $backup->file_size = $fileSize;
            $backup->save();
            $this->info("✓ Files backup created: {$fileName}");
            $this->info("  - Active files backed up: " . count($activeFiles));
        } else {
            throw new \Exception('Failed to create files backup');
        }
    }

    private function cleanupOldBackups()
    {
        // Daily backups - keep 7 days
        $sevenDaysAgo = now()->subDays(7);
        Backup::where('backup_type', 'database')
            ->where('backup_frequency', 'automatic')
            ->where('backup_date', '<', $sevenDaysAgo)
            ->each(function ($backup) {
                if (Storage::exists($backup->file_path)) {
                    Storage::delete($backup->file_path);
                }
                $backup->delete();
                $this->info("✓ Deleted old backup: {$backup->file_name}");
            });
    }
}
