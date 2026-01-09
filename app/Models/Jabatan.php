<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Jabatan extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'jabatan';
    protected $fillable = ['kode_jabatan', 'nama_jabatan', 'is_active'];

    public function getNamaAttribute()
    {
        return $this->nama_jabatan;
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
