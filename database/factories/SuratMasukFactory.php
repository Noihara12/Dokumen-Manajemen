<?php

namespace Database\Factories;

use App\Models\SuratMasuk;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class SuratMasukFactory extends Factory
{
    protected $model = SuratMasuk::class;

    public function definition(): array
    {
        $statuses = ['diterima', 'diproses', 'selesai'];
        $jenis = ['Biasa', 'Penting', 'Rahasia'];
        $disposisiOptions = ['Wakasek Kurikulum', 'Wakasek Sarana Prasarana', 'Wakasek Kesiswaan', 'Wakasek Humas', 'KA-TU', 'Kaprog DKV', 'Kaprog PPLG', 'Kaprog TJKT', 'Kaprog BD', 'BKK', 'Guru', 'Pembina Ektstra'];
        $isiDisposisiOptions = ['DIketahui', 'Diperbanyak', 'Dibahas', 'Difile', 'Diumumkan', 'Dibicarakan', 'Dilaksanakan', 'Dihubungi'];

        $tanggal = $this->faker->dateTimeBetween('2026-01-01', '2026-12-31');
        $nomor = $this->faker->unique()->numerify('###');
        $month = Carbon::parse($tanggal)->month;
        $year = Carbon::parse($tanggal)->year;

        return [
            'nomor_surat' => $nomor . '/SK/' . str_pad($month, 2, '0', STR_PAD_LEFT) . '/' . $year,
            'tanggal_surat' => $tanggal,
            'tanggal_diterima' => $this->faker->dateTimeBetween($tanggal, '2026-12-31'),
            'pengirim' => $this->faker->company(),
            'perihal' => $this->faker->sentence(5),
            'disposisi_ke' => implode(',', $this->faker->randomElements($disposisiOptions, $this->faker->numberBetween(2, 4))),
            'isi_disposisikan' => implode(',', $this->faker->randomElements($isiDisposisiOptions, $this->faker->numberBetween(1, 3))),
            'user_id' => User::inRandomOrder()->first()?->id ?? 1,
            'jenis_surat' => $this->faker->randomElement($jenis),
            'status' => $this->faker->randomElement($statuses),
            'catatan' => $this->faker->optional(0.3)->sentence(),
            'jumlah_lampiran' => $this->faker->numberBetween(0, 5),
        ];
    }
}
