<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SuratKeluar extends Model
{
    use HasFactory;

    protected $table = 'surat_keluar';
    protected $fillable = [
        'nomor_surat',
        'tanggal_surat',
        'tujuan',
        'perihal',
        'user_id',
        'catatan',
        'file_surat',
        'jumlah_lampiran',
        'status',
        'tanggal_pengiriman',
        'share_token',
        'is_shared',
        'shared_at'
    ];

    protected $casts = [
        'tanggal_surat' => 'date',
        'tanggal_pengiriman' => 'datetime',
        'shared_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Mendapatkan tipe file dari file_surat
     */
    public function getFileType()
    {
        if (!$this->file_surat) {
            return null;
        }

        $extension = pathinfo($this->file_surat, PATHINFO_EXTENSION);
        return strtolower($extension);
    }

    /**
     * Generate share token untuk public access
     */
    public function generateShareToken()
    {
        $this->share_token = \Illuminate\Support\Str::random(32);
        $this->is_shared = true;
        $this->shared_at = now();
        $this->save();
        return $this->share_token;
    }

    /**
     * Revoke share token
     */
    public function revokeShare()
    {
        $this->share_token = null;
        $this->is_shared = false;
        $this->shared_at = null;
        $this->save();
    }

    /**
     * Get public share link
     */
    public function getShareLink()
    {
        if ($this->share_token) {
            return route('surat-keluar.view-shared', $this->share_token);
        }
        return null;
    }
}
