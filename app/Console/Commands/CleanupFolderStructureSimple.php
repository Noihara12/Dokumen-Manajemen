<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CleanupFolderStructureSimple extends Command
{
    protected $signature = 'folders:cleanup-simple {--dry-run : Preview changes} {--force : Skip confirmation}';
    
    protected $description = 'Hapus nested storage folder di storage/app/public';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        $this->info('🔍 Scanning struktur folder...');
        
        $basePath = storage_path('app/public');
        $nestedStoragePath = $basePath . '/storage';

        if (!is_dir($nestedStoragePath)) {
            $this->info('✅ Tidak ada folder "storage" nested - struktur sudah rapi!');
            return 0;
        }

        // Count what's inside
        $files = 0;
        $folders = 0;
        
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($nestedStoragePath, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isFile()) {
                $files++;
            } elseif ($item->isDir()) {
                $folders++;
            }
        }

        $this->warn("⚠️  Ditemukan nested folder 'storage' dengan:");
        $this->line("   - Folders: $folders");
        $this->line("   - Files: $files");
        $this->newLine();

        $dirSize = $this->getDirectorySize($nestedStoragePath);
        $this->line("   Size: " . $this->formatBytes($dirSize));
        $this->newLine();

        $this->warn("Action: Hapus folder 'storage' dan semua isinya");
        $this->info("(Pastikan semua file yang perlu sudah di-backup!)");
        $this->newLine();

        if ($dryRun) {
            $this->info('📋 DRY RUN MODE - Tidak ada perubahan');
            return 0;
        }

        if (!$force) {
            if (!$this->confirm('Lanjutkan menghapus folder storage nested?', false)) {
                $this->info('❌ Dibatalkan');
                return 1;
            }
        }

        // Delete the nested storage folder
        try {
            $this->deleteDirectory($nestedStoragePath);
            $this->info('✅ Folder berhasil dihapus!');
            $this->line("   Folder rapi sekarang:");
            $this->line("   - storage/app/public/");
            $this->line("     ├── surat_masuk/");
            $this->line("     ├── surat_keluar/");
            $this->line("     └── assets/");
            return 0;
        } catch (\Exception $e) {
            $this->error('❌ Gagal menghapus folder: ' . $e->getMessage());
            return 1;
        }
    }

    private function deleteDirectory($path)
    {
        if (!is_dir($path)) {
            return true;
        }

        $items = scandir($path);
        
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $itemPath = $path . '/' . $item;

            if (is_dir($itemPath)) {
                $this->deleteDirectory($itemPath);
            } else {
                unlink($itemPath);
            }
        }

        return rmdir($path);
    }

    private function getDirectorySize($path)
    {
        $size = 0;
        
        if (is_dir($path)) {
            $items = scandir($path);
            foreach ($items as $item) {
                if ($item === '.' || $item === '..') {
                    continue;
                }
                
                $itemPath = $path . '/' . $item;
                if (is_file($itemPath)) {
                    $size += filesize($itemPath);
                } elseif (is_dir($itemPath)) {
                    $size += $this->getDirectorySize($itemPath);
                }
            }
        }

        return $size;
    }

    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
