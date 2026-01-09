<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'username',
        'password',
        'role_id',
        'unit_kerja_id',
        'jabatan',
        'nip',
        'no_telepon',
        'is_active'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    // Relasi
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function unitKerja()
    {
        return $this->belongsTo(UnitKerja::class);
    }

    public function suratMasuk()
    {
        return $this->hasMany(SuratMasuk::class);
    }

    public function suratKeluar()
    {
        return $this->hasMany(SuratKeluar::class);
    }

    public function disposisi()
    {
        return $this->hasMany(Disposisi::class, 'diteruskan_ke_id');
    }

    public function riwayatDisposisi()
    {
        return $this->hasMany(RiwayatDisposisi::class);
    }

    public function backups()
    {
        return $this->hasMany(Backup::class);
    }

    // Helper Methods
    public function hasRole($role)
    {
        return $this->role && $this->role->name === $role;
    }

    public function isAdmin()
    {
        return $this->hasRole('admin');
    }

    public function isTataUsaha()
    {
        return $this->hasRole('tata_usaha');
    }

    public function isStaff()
    {
        return $this->hasRole('staff');
    }

    // Backward compatibility alias
    public function isOperator()
    {
        return $this->isStaff();
    }
}
