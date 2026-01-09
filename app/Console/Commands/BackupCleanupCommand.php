<?php

namespace App\Console\Commands;

use App\Models\Backup;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class BackupCleanupCommand extends Command
{
    protected $signature = 'backup:cleanup {--days=7 : Number of days to keep}';
    protected $description = 'Cleanup old backup files based on retention policy';

    public function handle()
    {
        $this->info('🗑️ Starting backup cleanup...');

        try {
            $daysToKeep = $this->option('days');
            $cutoffDate = now()->subDays($daysToKeep);

            // Cleanup automatic backups
            $deleted = Backup::where('backup_frequency', 'automatic')
                ->where('backup_date', '<', $cutoffDate)
                ->where('status', 'completed')
                ->get();

            foreach ($deleted as $backup) {
                if (Storage::exists($backup->file_path)) {
                    Storage::delete($backup->file_path);
                    $this->info("✓ Deleted: {$backup->file_name}");
                }
                $backup->delete();
            }

            $count = $deleted->count();
            $this->info("✅ Cleanup completed! {$count} backup files removed.");

        } catch (\Exception $e) {
            $this->error('❌ Cleanup failed: ' . $e->getMessage());
        }
    }
}
