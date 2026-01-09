<?php

namespace Database\Factories;

use App\Models\SuratKeluar;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class SuratKeluarFactory extends Factory
{
    protected $model = SuratKeluar::class;

    public function definition(): array
    {
        $statuses = ['draft', 'dikirim', 'arsip'];
        $tujuan = [
            'Dinas Pendidikan Provinsi',
            'Kantor Walikota',
            'Kantor Bupati',
            'Dinas Kesehatan',
            'Dinas Sosial',
            'Lembaga Pendidikan Swasta',
            'Universitas Negeri',
            'Instansi Pemerintah Pusat',
            'Perusahaan Swasta',
            'Organisasi Kemasyarakatan'
        ];

        $tanggal = $this->faker->dateTimeBetween('2026-01-01', '2026-12-31');
        $nomor = $this->faker->unique()->numerify('###');
        $month = Carbon::parse($tanggal)->month;
        $year = Carbon::parse($tanggal)->year;

        return [
            'nomor_surat' => $nomor . '/SK/' . str_pad($month, 2, '0', STR_PAD_LEFT) . '/' . $year,
            'tanggal_surat' => $tanggal,
            'tujuan' => $this->faker->randomElement($tujuan),
            'perihal' => $this->faker->sentence(6),
            'user_id' => User::inRandomOrder()->first()?->id ?? 1,
            'status' => $this->faker->randomElement($statuses),
            'catatan' => $this->faker->optional(0.3)->sentence(),
            'tanggal_pengiriman' => $this->faker->optional(0.7)->dateTimeBetween($tanggal, '2026-12-31'),
        ];
    }
}
