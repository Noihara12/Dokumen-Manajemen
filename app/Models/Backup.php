<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Backup extends Model
{
    use HasFactory;

    protected $table = 'backups_dokumen_manajemen';

    protected $fillable = [
        'user_id',
        'backup_type',
        'file_path',
        'file_name',
        'file_size',
        'status',
        'notes',
        'backup_frequency',
        'backup_date',
        'completed_at',
    ];

    protected $casts = [
        'backup_date' => 'datetime',
        'completed_at' => 'datetime',
        'file_size' => 'integer',
    ];

    // Relasi
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeLatest($query)
    {
        return $query->orderBy('backup_date', 'desc');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('backup_type', $type);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    // Helpers
    public function getHumanReadableSize()
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2) . ' ' . $units[$pow];
    }

    public function getStatusBadgeClass()
    {
        return match($this->status) {
            'completed' => 'success',
            'processing' => 'warning',
            'failed' => 'danger',
            'pending' => 'info',
            default => 'secondary'
        };
    }

    public function getStatusLabel()
    {
        return match($this->status) {
            'completed' => 'Selesai',
            'processing' => 'Memproses',
            'failed' => 'Gagal',
            'pending' => 'Tertunda',
            default => 'Tidak Diketahui'
        };
    }
}
