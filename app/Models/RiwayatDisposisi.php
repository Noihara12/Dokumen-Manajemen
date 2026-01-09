<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RiwayatDisposisi extends Model
{
    use HasFactory;

    protected $table = 'riwayat_disposisi';
    protected $fillable = [
        'disposisi_id',
        'user_id',
        'aksi',
        'keterangan',
        'waktu_aksi'
    ];

    protected $casts = [
        'waktu_aksi' => 'datetime',
    ];

    public function disposisi()
    {
        return $this->belongsTo(Disposisi::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
