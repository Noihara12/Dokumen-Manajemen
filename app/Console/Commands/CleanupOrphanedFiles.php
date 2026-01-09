<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupOrphanedFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'files:cleanup-orphaned {--dry-run : Preview files to be deleted without actually deleting} {--force : Skip confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Bersihkan orphaned files dari storage yang tidak ada referensi di database';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        $this->info('🔍 Scanning untuk orphaned files di storage/app/public...');

        // Get semua active files dari database
        $activeFiles = [];
        
        $suratMasuk = DB::select('SELECT file_surat FROM surat_masuk WHERE file_surat IS NOT NULL AND file_surat != ""');
        foreach ($suratMasuk as $surat) {
            if (!empty($surat->file_surat)) {
                $activeFiles[] = $surat->file_surat;
            }
        }
        
        $suratKeluar = DB::select('SELECT file_surat FROM surat_keluar WHERE file_surat IS NOT NULL AND file_surat != ""');
        foreach ($suratKeluar as $surat) {
            if (!empty($surat->file_surat)) {
                $activeFiles[] = $surat->file_surat;
            }
        }

        // Get semua files di storage
        $allFiles = [];
        $storagePath = storage_path('app/public');
        
        if (is_dir($storagePath)) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($storagePath, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );
            
            foreach ($iterator as $fileInfo) {
                if ($fileInfo->isFile()) {
                    $relativePath = str_replace($storagePath . '/', '', $fileInfo->getRealPath());
                    $relativePath = str_replace('\\', '/', $relativePath); // Windows path fix
                    
                    // Skip system files
                    if (!in_array(basename($relativePath), ['.gitignore', '.htaccess', 'index.php', 'robots.txt', 'favicon.ico'])) {
                        $allFiles[] = [
                            'relative' => $relativePath,
                            'absolute' => $fileInfo->getRealPath(),
                            'size' => $fileInfo->getSize(),
                        ];
                    }
                }
            }
        }

        // Find orphaned files
        $orphanedFiles = [];
        foreach ($allFiles as $file) {
            if (!in_array($file['relative'], $activeFiles)) {
                // Skip the 2 active files
                $orphanedFiles[] = $file;
            }
        }

        if (count($orphanedFiles) === 0) {
            $this->info('✅ Tidak ada orphaned files!');
            return 0;
        }

        // Display summary
        $totalSize = array_sum(array_column($orphanedFiles, 'size'));
        $this->warn("⚠️  Ditemukan " . count($orphanedFiles) . " orphaned files");
        $this->line("   Total size: " . $this->formatBytes($totalSize));
        $this->newLine();

        // List orphaned files
        if (count($orphanedFiles) <= 20) {
            $this->info("Files yang akan dihapus:");
            foreach ($orphanedFiles as $file) {
                $this->line("   - " . $file['relative'] . " (" . $this->formatBytes($file['size']) . ")");
            }
        } else {
            $this->info("First 20 files yang akan dihapus:");
            foreach (array_slice($orphanedFiles, 0, 20) as $file) {
                $this->line("   - " . $file['relative'] . " (" . $this->formatBytes($file['size']) . ")");
            }
            $this->line("   ... dan " . (count($orphanedFiles) - 20) . " files lainnya");
        }
        $this->newLine();

        // Preview mode
        if ($dryRun) {
            $this->info("📋 DRY RUN MODE - Tidak ada file yang dihapus");
            return 0;
        }

        // Ask for confirmation
        if (!$force) {
            if (!$this->confirm("Apakah anda yakin ingin menghapus " . count($orphanedFiles) . " orphaned files?", false)) {
                $this->info("❌ Dibatalkan");
                return 1;
            }
        }

        // Delete files
        $deleted = 0;
        $failed = 0;
        
        foreach ($orphanedFiles as $file) {
            try {
                if (file_exists($file['absolute'])) {
                    unlink($file['absolute']);
                    $deleted++;
                    $this->line("   ✅ Dihapus: " . $file['relative']);
                }
            } catch (\Exception $e) {
                $failed++;
                $this->error("   ❌ Gagal: " . $file['relative'] . " - " . $e->getMessage());
            }
        }

        $this->newLine();
        $this->info("✅ Cleanup selesai!");
        $this->line("   Dihapus: $deleted files");
        if ($failed > 0) {
            $this->error("   Gagal: $failed files");
        }

        return 0;
    }

    /**
     * Format bytes ke human readable format
     */
    private function formatBytes($bytes, $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
