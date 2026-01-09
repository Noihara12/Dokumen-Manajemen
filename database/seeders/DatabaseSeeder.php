<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Buat Roles
        $adminRole = Role::firstOrCreate(
            ['name' => 'admin'],
            [
                'label' => 'Administrator',
                'description' => 'Mengelola seluruh sistem'
            ]
        );

        $tataUsahaRole = Role::firstOrCreate(
            ['name' => 'tata_usaha'],
            [
                'label' => 'Tata Usaha',
                'description' => 'Menangani persuratan'
            ]
        );

        $staffRole = Role::firstOrCreate(
            ['name' => 'staff'],
            [
                'label' => 'Staff',
                'description' => 'Staff dengan akses terbatas - Melihat Surat Masuk, Membuat Surat Keluar, Melihat Disposisi dan Arsip'
            ]
        );

        // Buat Users
        $admin = User::firstOrCreate(
            ['email' => 'admin@smkti.test'],
            [
                'name' => 'Administrator',
                'username' => 'admin',
                'password' => bcrypt('password'),
                'role_id' => $adminRole->id,
                'nip' => '123456789',
                'is_active' => true
            ]
        );

        $tatausaha = User::firstOrCreate(
            ['email' => 'tatausaha@smkti.test'],
            [
                'name' => 'Ni Made Ayu Suciati, S.Kom',
                'username' => 'tatausaha',
                'password' => bcrypt('password'),
                'role_id' => $tataUsahaRole->id,
                'nip' => '198702142015022003',
                'is_active' => true
            ]
        );

        $staff = User::firstOrCreate(
            ['email' => 'staff@smkti.test'],
            [
                'name' => 'Staff',
                'username' => 'staff',
                'password' => bcrypt('password'),
                'role_id' => $staffRole->id,
                'nip' => '199001012020011001',
                'is_active' => true
            ]
        );

        // Panggil SuratSeeder untuk membuat data dummy surat masuk dan surat keluar
        $this->call(SuratSeeder::class);
    }
}
