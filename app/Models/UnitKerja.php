<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UnitKerja extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'unit_kerja';
    protected $fillable = ['kode_unit', 'nama_unit', 'deskripsi', 'kepala_unit_id', 'is_active'];

    public function getNamaAttribute()
    {
        return $this->nama_unit;
    }

    public function kepalaUnit()
    {
        return $this->belongsTo(User::class, 'kepala_unit_id');
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function disposisi()
    {
        return $this->hasMany(Disposisi::class);
    }

    public function suratMasuk()
    {
        return $this->hasMany(SuratMasuk::class);
    }

    public function suratKeluar()
    {
        return $this->hasMany(SuratKeluar::class);
    }
}
