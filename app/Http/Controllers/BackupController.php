<?php

namespace App\Http\Controllers;

use App\Models\Backup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Artisan;
use Carbon\Carbon;

class BackupController extends Controller
{
    /**
     * Tampilkan daftar backup
     */
    public function index()
    {
        $backups = Backup::with('user')
            ->latest()
            ->paginate(15);

        $stats = [
            'total_backups' => Backup::count(),
            'total_size' => Backup::completed()->sum('file_size'),
            'last_backup' => Backup::completed()->latest('backup_date')->first(),
            'failed_backups' => Backup::where('status', 'failed')->count(),
        ];

        return view('admin.backup.index', compact('backups', 'stats'));
    }

    /**
     * Tampilkan form create backup manual
     */
    public function create()
    {
        return view('admin.backup.create');
    }

    /**
     * Simpan backup manual
     */
    public function store(Request $request)
    {
        $request->validate([
            'backup_type' => 'required|in:database,files,full',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $backup = new Backup();
            $backup->user_id = auth()->id();
            $backup->backup_type = $request->backup_type;
            $backup->status = 'processing';
            $backup->backup_frequency = 'manual';
            $backup->backup_date = now();
            $backup->notes = $request->notes;
            $backup->file_name = '';
            $backup->file_path = '';
            $backup->file_size = 0;
            $backup->save();

            // Jalankan backup
            $this->performBackup($backup);

            return redirect()->route('admin.backup.index')->with('success', 'Backup berhasil dibuat!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal membuat backup: ' . $e->getMessage());
        }
    }

    /**
     * Download backup file
     */
    public function download(Backup $backup)
    {
        // Validate user ownership atau admin privilege
        if (auth()->id() != $backup->user_id && !auth()->user()->isAdmin()) {
            abort(403);
        }

        $filePath = storage_path($backup->file_path);

        if (!file_exists($filePath)) {
            return redirect()->back()->with('error', 'File backup tidak ditemukan');
        }

        return response()->download($filePath, $backup->file_name);
    }

    /**
     * Restore dari backup
     */
    public function restore(Request $request, Backup $backup)
    {
        if (!auth()->user()->isAdmin()) {
            abort(403);
        }

        try {
            $request->validate([
                'confirm' => 'required|accepted',
            ]);

            if ($backup->backup_type == 'database' || $backup->backup_type == 'full') {
                $this->restoreDatabase($backup);
            }

            if ($backup->backup_type == 'files' || $backup->backup_type == 'full') {
                $this->restoreFiles($backup);
            }

            return redirect()->route('admin.backup.index')
                ->with('success', 'Restore backup berhasil dilakukan!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal restore: ' . $e->getMessage());
        }
    }

    /**
     * Hapus backup
     */
    public function destroy(Backup $backup)
    {
        if (!auth()->user()->isAdmin()) {
            abort(403);
        }

        try {
            if ($backup->file_path && Storage::exists($backup->file_path)) {
                Storage::delete($backup->file_path);
            }
            $backup->delete();

            return redirect()->back()->with('success', 'Backup berhasil dihapus!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menghapus backup: ' . $e->getMessage());
        }
    }

    /**
     * Cleanup old backups
     */
    public function cleanup()
    {
        if (!auth()->user()->isAdmin()) {
            abort(403);
        }

        try {
            $this->cleanupOldBackups();
            return redirect()->back()->with('success', 'Backup lama berhasil dihapus!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal cleanup: ' . $e->getMessage());
        }
    }

    /**
     * Perform backup operation
     */
    private function performBackup(Backup $backup)
    {
        try {
            $baseBackupDir = storage_path('backups');
            if (!is_dir($baseBackupDir)) {
                mkdir($baseBackupDir, 0755, true);
            }

            $timestamp = now()->format('Y-m-d_H-i-s');

            if ($backup->backup_type == 'database') {
                $this->backupDatabase($backup, $timestamp);
            } elseif ($backup->backup_type == 'files') {
                $this->backupFiles($backup, $timestamp);
            } elseif ($backup->backup_type == 'full') {
                $this->backupFull($backup, $timestamp);
            }

            $backup->status = 'completed';
            $backup->completed_at = now();
            $backup->save();

        } catch (\Exception $e) {
            $backup->status = 'failed';
            $backup->notes = $backup->notes . "\nError: " . $e->getMessage();
            $backup->save();
            throw $e;
        }
    }

    /**
     * Backup database - Simplified approach
     */
    private function backupDatabase(Backup $backup, $timestamp)
    {
        $backupDir = storage_path('backups/database');
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $fileName = "db_backup_{$timestamp}.sql";
        $filePath = $backupDir . '/' . $fileName;

        try {
            \Log::info('Starting database backup to: ' . $filePath);
            
            // Get all tables
            $tables = \DB::select('SHOW TABLES');
            $sql = "-- Database Backup\n";
            $sql .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
            $sql .= "-- Database: " . config('database.connections.mysql.database') . "\n";
            $sql .= "-- SET FOREIGN_KEY_CHECKS=0 before importing\n\n";

            $tableCount = 0;
            $rowCount = 0;
            
            foreach ($tables as $table) {
                $tableName = array_values((array)$table)[0];
                $tableCount++;
                
                \Log::debug('Backing up table: ' . $tableName);
                
                // Get CREATE TABLE
                $createTableResult = \DB::select("SHOW CREATE TABLE `{$tableName}`");
                if (!empty($createTableResult)) {
                    $sql .= $createTableResult[0]->{'Create Table'} . ";\n\n";
                }
                
                // Get INSERT DATA
                $rows = \DB::select("SELECT * FROM `{$tableName}`");
                if (!empty($rows)) {
                    foreach ($rows as $row) {
                        $row = (array)$row;
                        $columns = implode('`, `', array_keys($row));
                        
                        // Escape values properly
                        $sqlValues = [];
                        foreach (array_values($row) as $val) {
                            if ($val === null) {
                                $sqlValues[] = 'NULL';
                            } else {
                                // Simple escape for string values
                                if (is_string($val)) {
                                    $val = str_replace("'", "''", $val);
                                    $sqlValues[] = "'" . $val . "'";
                                } else {
                                    $sqlValues[] = (string)$val;
                                }
                            }
                        }
                        
                        $values = implode(', ', $sqlValues);
                        $sql .= "INSERT INTO `{$tableName}` (`{$columns}`) VALUES ({$values});\n";
                        $rowCount++;
                    }
                    $sql .= "\n";
                }
            }

            // Write to file
            if (file_put_contents($filePath, $sql) === false) {
                throw new \Exception('Gagal menulis file backup database');
            }

            if (file_exists($filePath) && filesize($filePath) > 0) {
                $fileSize = filesize($filePath);
                $backup->file_path = "backups/database/{$fileName}";
                $backup->file_name = $fileName;
                $backup->file_size = $fileSize;
                
                \Log::info("Database backup completed - Tables: {$tableCount}, Rows: {$rowCount}, Size: {$fileSize} bytes");
            } else {
                throw new \Exception('Database backup gagal atau file kosong');
            }
        } catch (\Exception $e) {
            \Log::error('Database backup error: ' . $e->getMessage());
            throw new \Exception('Database backup error: ' . $e->getMessage());
        }
    }

    /**
     * Backup files - termasuk file surat dan lampiran
     */
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
            throw new \Exception('Gagal membuat zip file');
        }

        $activeFiles = [];
        $storagePath = storage_path('app/public');
        
        // Check if lampiran_surat table exists
        $tableExists = \Schema::hasTable('lampiran_surat');
        
        // Get file surat dari surat masuk
        $suratMasuk = \DB::select('SELECT id, file_surat FROM surat_masuk WHERE file_surat IS NOT NULL AND file_surat != ""');
        foreach ($suratMasuk as $surat) {
            if (!empty($surat->file_surat)) {
                $activeFiles[$surat->file_surat] = true;
            }
            
            // Backup lampiran dari surat masuk (jika tabel ada)
            if ($tableExists) {
                try {
                    $lampiran = \DB::select(
                        'SELECT file_lampiran FROM lampiran_surat WHERE surat_id = ? AND jenis_surat = ? AND file_lampiran IS NOT NULL AND file_lampiran != ""',
                        [$surat->id, 'masuk']
                    );
                    foreach ($lampiran as $attach) {
                        if (!empty($attach->file_lampiran)) {
                            $activeFiles[$attach->file_lampiran] = true;
                        }
                    }
                } catch (\Exception $e) {
                    // Skip lampiran if query fails
                    \Log::warning('Lampiran backup error untuk surat masuk: ' . $e->getMessage());
                }
            }
        }
        
        // Get file surat dari surat keluar
        $suratKeluar = \DB::select('SELECT id, file_surat FROM surat_keluar WHERE file_surat IS NOT NULL AND file_surat != ""');
        foreach ($suratKeluar as $surat) {
            if (!empty($surat->file_surat)) {
                $activeFiles[$surat->file_surat] = true;
            }
            
            // Backup lampiran dari surat keluar (jika tabel ada)
            if ($tableExists) {
                try {
                    $lampiran = \DB::select(
                        'SELECT file_lampiran FROM lampiran_surat WHERE surat_id = ? AND jenis_surat = ? AND file_lampiran IS NOT NULL AND file_lampiran != ""',
                        [$surat->id, 'keluar']
                    );
                    foreach ($lampiran as $attach) {
                        if (!empty($attach->file_lampiran)) {
                            $activeFiles[$attach->file_lampiran] = true;
                        }
                    }
                } catch (\Exception $e) {
                    // Skip lampiran if query fails
                    \Log::warning('Lampiran backup error untuk surat keluar: ' . $e->getMessage());
                }
            }
        }
        
        // Add semua active files ke zip dengan struktur folder yang sama
        foreach ($activeFiles as $fileRef => $dummy) {
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
        } else {
            throw new \Exception('Files backup gagal atau file kosong');
        }
    }

    /**
     * Full backup (database + files)
     */
    private function backupFull(Backup $backup, $timestamp)
    {
        $backupDir = storage_path('backups/full');
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $fileName = "full_backup_{$timestamp}.zip";
        $filePath = $backupDir . '/' . $fileName;

        $zip = new \ZipArchive();
        if ($zip->open($filePath, \ZipArchive::CREATE) !== true) {
            throw new \Exception('Gagal membuat zip file untuk full backup');
        }

        // Backup database to temporary file dan add ke zip
        $dbBackupDir = storage_path('backups/temp_db');
        if (!is_dir($dbBackupDir)) {
            mkdir($dbBackupDir, 0755, true);
        }

        $dbFileName = "database_{$timestamp}.sql";
        $dbFilePath = $dbBackupDir . '/' . $dbFileName;

        try {
            // Get all tables menggunakan PHP (tidak mysqldump)
            $tables = \DB::select('SHOW TABLES');
            $sql = "-- Database Backup\n";
            $sql .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
            $sql .= "-- Database: " . config('database.connections.mysql.database') . "\n\n";

            foreach ($tables as $table) {
                $tableName = array_values((array)$table)[0];
                
                // Get CREATE TABLE
                $createTableResult = \DB::select("SHOW CREATE TABLE `{$tableName}`");
                $sql .= $createTableResult[0]->{'Create Table'} . ";\n\n";
                
                // Get INSERT DATA
                $rows = \DB::select("SELECT * FROM `{$tableName}`");
                if (!empty($rows)) {
                    foreach ($rows as $row) {
                        $row = (array)$row;
                        $columns = implode('`, `', array_keys($row));
                        
                        // Use proper SQL value escaping
                        $sqlValues = [];
                        foreach (array_values($row) as $val) {
                            if ($val === null) {
                                $sqlValues[] = 'NULL';
                            } else {
                                // Escape for MySQL using PDO quote
                                $escaped = \DB::connection()->getPdo()->quote((string)$val);
                                $sqlValues[] = $escaped;
                            }
                        }
                        
                        $values = implode(', ', $sqlValues);
                        $sql .= "INSERT INTO `{$tableName}` (`{$columns}`) VALUES ({$values});\n";
                    }
                    $sql .= "\n";
                }
            }

            // Write to temporary file
            if (file_put_contents($dbFilePath, $sql) === false) {
                throw new \Exception('Gagal menulis file database backup');
            }

            // Add ke zip
            if (file_exists($dbFilePath) && filesize($dbFilePath) > 0) {
                $zip->addFile($dbFilePath, 'database/' . $dbFileName);
            } else {
                $zip->close();
                throw new \Exception('Database backup untuk full backup gagal');
            }
        } catch (\Exception $e) {
            $zip->close();
            throw $e;
        }

        // Add application files to zip - termasuk file surat dan lampiran
        $activeFiles = [];
        $storagePath = storage_path('app/public');
        
        // Check if lampiran_surat table exists
        $tableExists = \Schema::hasTable('lampiran_surat');
        
        // Get file surat dari surat masuk
        $suratMasuk = \DB::select('SELECT id, file_surat FROM surat_masuk WHERE file_surat IS NOT NULL AND file_surat != ""');
        foreach ($suratMasuk as $surat) {
            if (!empty($surat->file_surat)) {
                $activeFiles[$surat->file_surat] = true;
            }
            
            // Backup lampiran dari surat masuk (jika tabel ada)
            if ($tableExists) {
                try {
                    $lampiran = \DB::select(
                        'SELECT file_lampiran FROM lampiran_surat WHERE surat_id = ? AND jenis_surat = ? AND file_lampiran IS NOT NULL AND file_lampiran != ""',
                        [$surat->id, 'masuk']
                    );
                    foreach ($lampiran as $attach) {
                        if (!empty($attach->file_lampiran)) {
                            $activeFiles[$attach->file_lampiran] = true;
                        }
                    }
                } catch (\Exception $e) {
                    // Skip lampiran if query fails
                    \Log::warning('Lampiran backup error untuk surat masuk: ' . $e->getMessage());
                }
            }
        }
        
        // Get file surat dari surat keluar
        $suratKeluar = \DB::select('SELECT id, file_surat FROM surat_keluar WHERE file_surat IS NOT NULL AND file_surat != ""');
        foreach ($suratKeluar as $surat) {
            if (!empty($surat->file_surat)) {
                $activeFiles[$surat->file_surat] = true;
            }
            
            // Backup lampiran dari surat keluar (jika tabel ada)
            if ($tableExists) {
                try {
                    $lampiran = \DB::select(
                        'SELECT file_lampiran FROM lampiran_surat WHERE surat_id = ? AND jenis_surat = ? AND file_lampiran IS NOT NULL AND file_lampiran != ""',
                        [$surat->id, 'keluar']
                    );
                    foreach ($lampiran as $attach) {
                        if (!empty($attach->file_lampiran)) {
                            $activeFiles[$attach->file_lampiran] = true;
                        }
                    }
                } catch (\Exception $e) {
                    // Skip lampiran if query fails
                    \Log::warning('Lampiran backup error untuk surat keluar: ' . $e->getMessage());
                }
            }
        }
        
        // Add semua active files ke zip dengan struktur folder yang benar
        foreach ($activeFiles as $fileRef => $dummy) {
            $fullPath = $storagePath . '/' . $fileRef;
            if (file_exists($fullPath) && is_file($fullPath)) {
                // Preserve folder structure: surat_masuk/xxx.pdf -> surat_masuk/xxx.pdf
                $zip->addFile($fullPath, $fileRef);
            }
        }

        // Add public files
        $publicPath = public_path();
        if (is_dir($publicPath)) {
            $this->addFilesToZip($zip, $publicPath, 'public');
        }

        $zip->close();

        // Clean up temporary database file
        if (file_exists($dbFilePath)) {
            unlink($dbFilePath);
        }

        if (file_exists($filePath) && filesize($filePath) > 0) {
            $fileSize = filesize($filePath);
            $backup->file_path = "backups/full/{$fileName}";
            $backup->file_name = $fileName;
            $backup->file_size = $fileSize;
        } else {
            throw new \Exception('Full backup gagal atau file kosong');
        }
    }

    /**
     * Add files to zip recursively
     */
    private function addFilesToZip(\ZipArchive $zip, $path, $localPath)
    {
        if (!is_dir($path)) {
            return;
        }

        $files = @scandir($path);
        if ($files === false) {
            return;
        }

        foreach ($files as $file) {
            if ($file == '.' || $file == '..') {
                continue;
            }

            $filePath = $path . '/' . $file;
            $newLocalPath = trim($localPath . '/' . $file, '/');

            // Skip system directories
            if (in_array($file, ['cache', 'logs', '.git', '.env'])) {
                continue;
            }

            try {
                if (is_file($filePath) && is_readable($filePath)) {
                    $zip->addFile($filePath, $newLocalPath);
                } elseif (is_dir($filePath) && is_readable($filePath)) {
                    $this->addFilesToZip($zip, $filePath, $newLocalPath);
                }
            } catch (\Exception $e) {
                // Log error but continue with other files
                continue;
            }
        }
    }

    /**
     * Restore database - Simplified approach
     */
    private function restoreDatabase(Backup $backup)
    {
        $filePath = storage_path($backup->file_path);

        if (!file_exists($filePath)) {
            throw new \Exception('File backup database tidak ditemukan: ' . $filePath);
        }

        try {
            \Log::info('Starting database restore from: ' . $filePath);
            
            // Disable foreign key checks
            \DB::statement('SET FOREIGN_KEY_CHECKS=0');
            
            // Read SQL file
            $sql = file_get_contents($filePath);
            if ($sql === false || empty($sql)) {
                throw new \Exception('Gagal membaca file backup atau file kosong');
            }

            // Fix encoding
            $sql = mb_convert_encoding($sql, 'UTF-8', 'UTF-8');
            
            // Simple split by semicolon
            $statements = preg_split('/;[\r\n]+/', $sql);
            $executedCount = 0;
            $skippedCount = 0;
            $errorCount = 0;
            
            foreach ($statements as $statement) {
                $statement = trim($statement);
                
                // Skip empty statements
                if (empty($statement)) {
                    continue;
                }
                
                // Skip comments
                if (strpos($statement, '--') === 0 || strpos($statement, '/*') === 0) {
                    $skippedCount++;
                    continue;
                }
                
                // Skip CREATE TABLE statements (tables sudah ada dari migration)
                if (stripos($statement, 'CREATE TABLE') === 0) {
                    \Log::debug('Skipped CREATE TABLE statement');
                    $skippedCount++;
                    continue;
                }
                
                try {
                    // Extract table name untuk TRUNCATE jika perlu
                    if (stripos($statement, 'INSERT INTO') === 0) {
                        preg_match('/INSERT INTO `?([^`\s]+)`?/i', $statement, $matches);
                        if (!empty($matches[1])) {
                            $tableName = $matches[1];
                            // TRUNCATE table sebelum insert (hanya sekali per table)
                            static $truncatedTables = [];
                            if (!isset($truncatedTables[$tableName])) {
                                try {
                                    \DB::statement("TRUNCATE TABLE `{$tableName}`");
                                    $truncatedTables[$tableName] = true;
                                    \Log::debug("Truncated table: {$tableName}");
                                } catch (\Exception $e) {
                                    \Log::warning("Failed to truncate table {$tableName}: " . $e->getMessage());
                                }
                            }
                        }
                    }
                    
                    // Execute statement
                    \DB::statement($statement);
                    $executedCount++;
                    
                } catch (\Exception $e) {
                    $errorCount++;
                    \Log::warning('SQL Execution Error: ' . $e->getMessage() . ' | Statement: ' . substr($statement, 0, 100));
                }
            }
            
            // Re-enable foreign key checks
            \DB::statement('SET FOREIGN_KEY_CHECKS=1');
            
            \Log::info("Database restore completed - Executed: {$executedCount}, Skipped: {$skippedCount}, Errors: {$errorCount}");
            
            if ($executedCount === 0 && $errorCount > 0) {
                throw new \Exception("Database restore gagal - No statements executed. Errors: {$errorCount}");
            }
            
        } catch (\Exception $e) {
            try {
                \DB::statement('SET FOREIGN_KEY_CHECKS=1');
            } catch (\Exception $ex) {
                // Ignore error when re-enabling foreign key checks
            }
            throw new \Exception('Database restore error: ' . $e->getMessage());
        }
    }

    /**
     * Restore files - dengan cleaning dan verifikasi ketat
     */
    private function restoreFiles(Backup $backup)
    {
        $filePath = storage_path($backup->file_path);

        if (!file_exists($filePath)) {
            throw new \Exception('File backup tidak ditemukan: ' . $filePath);
        }

        try {
            // Step 1: Tentukan path yang tepat
            $storagePath = storage_path('app/public');
            $suratMasukPath = $storagePath . '/surat_masuk';
            $suratKeluarPath = $storagePath . '/surat_keluar';
            $lampiranPath = $storagePath . '/lampiran';
            
            \Log::info('Restore - Starting restore process');
            \Log::info('Restore - Storage path: ' . $storagePath);
            \Log::info('Restore - Backup file: ' . $filePath);
            
            // Step 2: Buat folder jika belum ada
            if (!is_dir($suratMasukPath)) {
                mkdir($suratMasukPath, 0755, true);
                \Log::info('Restore - Created surat_masuk folder');
            }
            if (!is_dir($suratKeluarPath)) {
                mkdir($suratKeluarPath, 0755, true);
                \Log::info('Restore - Created surat_keluar folder');
            }
            if (!is_dir($lampiranPath)) {
                mkdir($lampiranPath, 0755, true);
                \Log::info('Restore - Created lampiran folder');
            }

            // Step 3: Hapus folder storage nested jika ada (fix double nesting)
            $nestedStoragePath = $storagePath . '/storage';
            if (is_dir($nestedStoragePath)) {
                \Log::info('Restore - Found nested storage folder, removing it...');
                $this->recursiveDeleteDir($nestedStoragePath);
                \Log::info('Restore - Nested storage folder removed');
            }

            // Step 4: Hapus file lama dengan aman
            \Log::info('Restore - Deleting old files...');
            $this->safeDeleteFiles($suratMasukPath);
            $this->safeDeleteFiles($suratKeluarPath);
            $this->safeDeleteFiles($lampiranPath);
            \Log::info('Restore - Old files deleted');

            // Step 5: Extract backup dengan verifikasi
            \Log::info('Restore - Extracting backup zip...');
            $zip = new \ZipArchive();
            if ($zip->open($filePath) !== true) {
                throw new \Exception('Gagal membuka file backup zip: ' . $filePath);
            }

            $extractPath = storage_path('app');
            \Log::info('Restore - Extract path: ' . $extractPath);
            
            if (!$zip->extractTo($extractPath)) {
                $zip->close();
                throw new \Exception('Gagal extract file backup ke: ' . $extractPath);
            }
            
            $numFiles = $zip->numFiles;
            \Log::info('Restore - Extracted ' . $numFiles . ' files from backup');
            $zip->close();

            // Step 6: Tunggu file system selesai menulis
            sleep(1);

            // Step 7: Handle nested storage folder yang ter-extract (jika ada)
            if (is_dir($nestedStoragePath)) {
                \Log::info('Restore - Handling nested storage folder from backup...');
                
                // Move file dari nested storage ke parent
                $nestedMasuk = $nestedStoragePath . '/surat_masuk';
                $nestedKeluar = $nestedStoragePath . '/surat_keluar';
                $nestedLampiran = $nestedStoragePath . '/lampiran';
                
                if (is_dir($nestedMasuk)) {
                    $this->moveFilesFromNestedDir($nestedMasuk, $suratMasukPath);
                }
                if (is_dir($nestedKeluar)) {
                    $this->moveFilesFromNestedDir($nestedKeluar, $suratKeluarPath);
                }
                if (is_dir($nestedLampiran)) {
                    $this->moveFilesFromNestedDir($nestedLampiran, $lampiranPath);
                }
                
                // Remove nested storage folder
                $this->recursiveDeleteDir($nestedStoragePath);
                \Log::info('Restore - Nested storage folder processed and removed');
            }
            
            // Step 8: Verify file berhasil di-extract
            $suratMasukCount = $this->countFiles($suratMasukPath);
            $suratKeluarCount = $this->countFiles($suratKeluarPath);
            $lampiranCount = $this->countFiles($lampiranPath);

            \Log::info('Restore - Surat Masuk count: ' . $suratMasukCount);
            \Log::info('Restore - Surat Keluar count: ' . $suratKeluarCount);
            \Log::info('Restore - Lampiran count: ' . $lampiranCount);

            if ($suratMasukCount == 0 && $suratKeluarCount == 0) {
                throw new \Exception('File surat tidak ditemukan dalam backup. Folder surat_masuk dan surat_keluar kosong. Pastikan backup file berisi file surat.');
            }

            \Log::info('Restore - Restore completed successfully');

        } catch (\Exception $e) {
            \Log::error('Restore Files Error: ' . $e->getMessage());
            throw new \Exception('Restore files gagal: ' . $e->getMessage());
        }
    }

    /**
     * Safely delete all files in a directory recursively
     */
    private function safeDeleteFiles($dirPath)
    {
        if (!is_dir($dirPath)) {
            return;
        }

        $files = @scandir($dirPath);
        if ($files === false) {
            \Log::warning('Cannot scan directory: ' . $dirPath);
            return;
        }

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $filePath = $dirPath . '/' . $file;
            
            if (is_file($filePath)) {
                try {
                    if (@unlink($filePath)) {
                        \Log::debug('Deleted file: ' . $filePath);
                    } else {
                        \Log::warning('Failed to delete file: ' . $filePath);
                    }
                } catch (\Exception $e) {
                    \Log::warning('Exception deleting file ' . $filePath . ': ' . $e->getMessage());
                }
            } elseif (is_dir($filePath)) {
                // Recursively delete subdirectories
                $this->safeDeleteFiles($filePath);
                try {
                    @rmdir($filePath);
                    \Log::debug('Deleted directory: ' . $filePath);
                } catch (\Exception $e) {
                    \Log::warning('Failed to delete directory: ' . $filePath);
                }
            }
        }
    }

    /**
     * Recursively delete a directory and all its contents
     */
    private function recursiveDeleteDir($dirPath)
    {
        if (!is_dir($dirPath)) {
            return;
        }

        $files = @scandir($dirPath);
        if ($files === false) {
            return;
        }

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $filePath = $dirPath . '/' . $file;
            
            if (is_file($filePath)) {
                @unlink($filePath);
            } elseif (is_dir($filePath)) {
                $this->recursiveDeleteDir($filePath);
            }
        }
        
        @rmdir($dirPath);
    }

    /**
     * Move files from nested directory to target directory
     */
    private function moveFilesFromNestedDir($sourceDir, $targetDir)
    {
        if (!is_dir($sourceDir) || !is_dir($targetDir)) {
            return;
        }

        $files = @scandir($sourceDir);
        if ($files === false) {
            return;
        }

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $sourceFile = $sourceDir . '/' . $file;
            $targetFile = $targetDir . '/' . $file;

            if (is_file($sourceFile)) {
                try {
                    if (@rename($sourceFile, $targetFile)) {
                        \Log::info('Moved file: ' . $sourceFile . ' to ' . $targetFile);
                    } else {
                        // If rename fails, try copy
                        if (@copy($sourceFile, $targetFile)) {
                            @unlink($sourceFile);
                            \Log::info('Copied and removed file: ' . $sourceFile . ' to ' . $targetFile);
                        }
                    }
                } catch (\Exception $e) {
                    \Log::warning('Failed to move file ' . $sourceFile . ': ' . $e->getMessage());
                }
            }
        }
    }

    /**
     * Count files in directory (exclude . dan ..)
     */
    private function countFiles($dirPath)
    {
        if (!is_dir($dirPath)) {
            return 0;
        }

        $files = @scandir($dirPath);
        if ($files === false) {
            return 0;
        }

        // Count actual files (exclude . dan ..)
        $count = 0;
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..') {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Cleanup old backups based on retention policy
     */
    private function cleanupOldBackups()
    {
        // Daily backups - keep 7 days
        $sevenDaysAgo = now()->subDays(7);
        Backup::where('backup_type', 'database')
            ->where('backup_date', '<', $sevenDaysAgo)
            ->each(function ($backup) {
                if (Storage::exists($backup->file_path)) {
                    Storage::delete($backup->file_path);
                }
                $backup->delete();
            });

        // Weekly backups - keep 4 weeks
        $fourWeeksAgo = now()->subWeeks(4);
        Backup::where('backup_type', 'files')
            ->where('backup_date', '<', $fourWeeksAgo)
            ->each(function ($backup) {
                if (Storage::exists($backup->file_path)) {
                    Storage::delete($backup->file_path);
                }
                $backup->delete();
            });
    }
}
