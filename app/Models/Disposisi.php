<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Disposisi extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'disposisi';
    protected $fillable = [
        'surat_masuk_id',
        'unit_kerja_id',
        'diteruskan_ke',
        'instruksi',
        'batas_waktu',
        'status',
        'catatan'
    ];

    protected $casts = [
        'batas_waktu' => 'date',
    ];

    public function suratMasuk()
    {
        return $this->belongsTo(SuratMasuk::class);
    }

    public function unitKerja()
    {
        return $this->belongsTo(UnitKerja::class);
    }

    public function riwayat()
    {
        return $this->hasMany(RiwayatDisposisi::class);
    }
}
