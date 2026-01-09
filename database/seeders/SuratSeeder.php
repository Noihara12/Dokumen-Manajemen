<?php

namespace Database\Seeders;

use App\Models\SuratMasuk;
use App\Models\SuratKeluar;
use App\Models\Disposisi;
use Illuminate\Database\Seeder;

class SuratSeeder extends Seeder
{
    /**
     * Seed the surat_masuk and surat_keluar tables with dummy data.
     */
    public function run(): void
    {
        // Buat 50 data surat masuk
        $suratMasukList = SuratMasuk::factory()
            ->count(50)
            ->create();

        // Buat disposisi untuk setiap surat masuk
        foreach ($suratMasukList as $surat) {
            try {
                Disposisi::create([
                    'surat_masuk_id' => $surat->id,
                    'diteruskan_ke' => $surat->disposisi_ke,
                    'instruksi' => 'Surat dari ' . $surat->pengirim . ' - ' . $surat->perihal,
                    'status' => 'baru'
                ]);
            } catch (\Exception $e) {
                \Log::warning('Error creating disposisi for surat masuk ' . $surat->id);
            }
        }

        // Buat 50 data surat keluar
        SuratKeluar::factory()
            ->count(50)
            ->create();

        $this->command->info('✓ Berhasil membuat 50 data Surat Masuk + Disposisi dan 50 data Surat Keluar untuk periode Januari-Desember 2026');
    }
}
