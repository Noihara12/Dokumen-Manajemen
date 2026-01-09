<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\UnitKerja;

class UnitKerjaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $unitKerjaList = [
            [
                'kode_unit' => 'WAKASEK-KURIKULUM',
                'nama_unit' => 'Wakasek Kurikulum',
                'deskripsi' => 'Wakil Kepala Sekolah Bidang Kurikulum dan Pembelajaran',
                'is_active' => true,
            ],
            [
                'kode_unit' => 'WAKASEK-KESISWAAN',
                'nama_unit' => 'Wakasek Kesiswaan',
                'deskripsi' => 'Wakil Kepala Sekolah Bidang Kesiswaan',
                'is_active' => true,
            ],
            [
                'kode_unit' => 'KA-TU',
                'nama_unit' => 'KA-TU',
                'deskripsi' => 'Kepala Tata Usaha',
                'is_active' => true,
            ],
            [
                'kode_unit' => 'KAPROG-PPLG',
                'nama_unit' => 'Kaprog PPLG',
                'deskripsi' => 'Kepala Program Teknik Komputer dan Jaringan',
                'is_active' => true,
            ],
            [
                'kode_unit' => 'KAPROG-BD',
                'nama_unit' => 'Kaprog BD',
                'deskripsi' => 'Kepala Program Bisnis Daring',
                'is_active' => true,
            ],
            [
                'kode_unit' => 'KAPROG-TJKT',
                'nama_unit' => 'Kaprog TJKT',
                'deskripsi' => 'Kepala Program Teknik Jaringan Komputer Terpadu',
                'is_active' => true,
            ],
            [
                'kode_unit' => 'KAPROG-DKV',
                'nama_unit' => 'Kaprog DKV',
                'deskripsi' => 'Kepala Program Desain Komunikasi Visual',
                'is_active' => true,
            ],
            [
                'kode_unit' => 'BKK',
                'nama_unit' => 'BKK',
                'deskripsi' => 'Bursa Kerja Khusus',
                'is_active' => true,
            ],
            [
                'kode_unit' => 'PEMBINA-EKSTRAKURIKULER',
                'nama_unit' => 'Pembina Ekstrakurikuler',
                'deskripsi' => 'Pembina Kegiatan Ekstrakurikuler',
                'is_active' => true,
            ],
            [
                'kode_unit' => 'GURU',
                'nama_unit' => 'Guru',
                'deskripsi' => 'Guru Kelas/Mata Pelajaran',
                'is_active' => true,
            ],
        ];

        foreach ($unitKerjaList as $unitKerja) {
            UnitKerja::firstOrCreate(
                ['kode_unit' => $unitKerja['kode_unit']],
                $unitKerja
            );
        }
    }
}
