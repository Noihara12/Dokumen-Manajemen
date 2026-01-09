<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupFolderStructure extends Command
{
    protected $signature = 'folders:cleanup-structure {--dry-run : Preview changes without making them} {--force : Skip confirmation}';
    
    protected $description = 'Rapikan struktur folder storage yang nested/duplikat';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        $this->info('🔍 Analyzing folder structure di storage/app/public...');
        
        $basePath = storage_path('app/public');
        
        // Analyze current structure
        $issues = [];
        $filesToMove = [];
        
        // Check for redundant nested folders
        if (is_dir($basePath . '/storage')) {
            $this->warn('⚠️  Ditemukan folder nested "storage" di dalam storage/app/public!');
            $this->analyzeNestedFolders($basePath . '/storage', $basePath, $issues, $filesToMove);
        }

        // Check database references
        $this->checkDatabaseReferences($basePath, $filesToMove);

        if (empty($issues) && empty($filesToMove)) {
            $this->info('✅ Folder structure sudah rapi!');
            return 0;
        }

        // Display issues
        if (!empty($issues)) {
            $this->warn("\n⚠️  Issues ditemukan:");
            foreach ($issues as $issue) {
                $this->line("   - " . $issue);
            }
        }

        // Display files to move
        if (!empty($filesToMove)) {
            $this->warn("\n📁 Files yang perlu dipindahkan:");
            foreach ($filesToMove as $file) {
                $this->line("   - FROM: {$file['from']}");
                $this->line("     TO:   {$file['to']}");
            }
        }

        $this->newLine();

        if ($dryRun) {
            $this->info('📋 DRY RUN MODE - Tidak ada perubahan');
            return 0;
        }

        // Confirmation
        if (!$force) {
            if (!$this->confirm('Lanjutkan membersihkan folder structure?', false)) {
                $this->info('❌ Dibatalkan');
                return 1;
            }
        }

        // Execute cleanup
        $moved = 0;
        $failed = 0;
        $deleted = 0;

        // Move files to correct locations
        foreach ($filesToMove as $file) {
            try {
                $toDir = dirname($file['to']);
                if (!is_dir($toDir)) {
                    mkdir($toDir, 0755, true);
                }
                
                if (rename($file['from'], $file['to'])) {
                    $moved++;
                    $this->line("   ✅ Pindahkan: " . basename($file['from']));
                } else {
                    $failed++;
                    $this->error("   ❌ Gagal pindahkan: " . basename($file['from']));
                }
            } catch (\Exception $e) {
                $failed++;
                $this->error("   ❌ Error: " . $e->getMessage());
            }
        }

        // Delete empty nested storage folders
        $this->deleteEmptyNestedFolders($basePath . '/storage', $deleted);

        $this->newLine();
        $this->info('✅ Cleanup selesai!');
        $this->line("   Files dipindahkan: $moved");
        if ($failed > 0) {
            $this->error("   Gagal: $failed");
        }
        $this->line("   Folders dihapus: $deleted");

        return 0;
    }

    private function analyzeNestedFolders($path, $basePath, &$issues, &$filesToMove, $depth = 0)
    {
        if ($depth > 3) {
            return; // Stop if too deep
        }

        if (!is_dir($path)) {
            return;
        }

        $items = scandir($path);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $itemPath = $path . '/' . $item;

            if (is_dir($itemPath)) {
                // Check if it's surat_masuk or surat_keluar
                if (in_array($item, ['surat_masuk', 'surat_keluar'])) {
                    $relativePath = str_replace($basePath . '/', '', $itemPath);
                    $issues[] = "Folder '{$item}' berada di: {$relativePath} (harusnya di root)";
                    
                    // Add files from this folder to move list
                    $this->collectFilesToMove($itemPath, $basePath . '/' . $item, $filesToMove);
                } elseif ($item === 'storage') {
                    // Nested storage folder - go deeper
                    $this->analyzeNestedFolders($itemPath, $basePath, $issues, $filesToMove, $depth + 1);
                }
            }
        }
    }

    private function collectFilesToMove($sourcePath, $targetPath, &$filesToMove)
    {
        if (!is_dir($sourcePath)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourcePath, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $fileInfo) {
            if ($fileInfo->isFile()) {
                $relativeToSource = str_replace($sourcePath . '/', '', $fileInfo->getRealPath());
                $filesToMove[] = [
                    'from' => $fileInfo->getRealPath(),
                    'to' => $targetPath . '/' . $relativeToSource
                ];
            }
        }
    }

    private function checkDatabaseReferences($basePath, &$filesToMove)
    {
        // Get all files currently referenced in database
        $activeFiles = [];
        
        $suratMasuk = DB::select('SELECT file_surat FROM surat_masuk WHERE file_surat IS NOT NULL');
        foreach ($suratMasuk as $surat) {
            if (!empty($surat->file_surat)) {
                $activeFiles[] = $surat->file_surat;
            }
        }
        
        $suratKeluar = DB::select('SELECT file_surat FROM surat_keluar WHERE file_surat IS NOT NULL');
        foreach ($suratKeluar as $surat) {
            if (!empty($surat->file_surat)) {
                $activeFiles[] = $surat->file_surat;
            }
        }

        // Check which files don't exist in correct location
        foreach ($activeFiles as $file) {
            $correctPath = $basePath . '/' . $file;
            
            if (!file_exists($correctPath)) {
                // Search for file in nested folders
                $found = false;
                $pattern = basename($file);
                
                $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($basePath, \RecursiveDirectoryIterator::SKIP_DOTS),
                    \RecursiveIteratorIterator::SELF_FIRST
                );

                foreach ($iterator as $fileInfo) {
                    if ($fileInfo->isFile() && $fileInfo->getFilename() === $pattern) {
                        $filesToMove[] = [
                            'from' => $fileInfo->getRealPath(),
                            'to' => $correctPath
                        ];
                        $found = true;
                        break;
                    }
                }
            }
        }
    }

    private function deleteEmptyNestedFolders($path, &$deleted)
    {
        if (!is_dir($path)) {
            return;
        }

        $items = scandir($path);
        
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $itemPath = $path . '/' . $item;

            if (is_dir($itemPath)) {
                // Recursively cleanup subdirectories first
                $this->deleteEmptyNestedFolders($itemPath, $deleted);
                
                // Check if directory is empty now
                if (count(scandir($itemPath)) === 2) { // Only . and ..
                    try {
                        rmdir($itemPath);
                        $deleted++;
                    } catch (\Exception $e) {
                        // Ignore
                    }
                }
            }
        }
    }
}
