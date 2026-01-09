<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class KlasifikasiSurat extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'klasifikasi_surat';
    protected $fillable = ['kode_klasifikasi', 'nama_klasifikasi', 'deskripsi', 'warna', 'masa_retensi', 'is_active'];

    public function suratMasuk()
    {
        return $this->hasMany(SuratMasuk::class);
    }

    public function suratKeluar()
    {
        return $this->hasMany(SuratKeluar::class);
    }
}
