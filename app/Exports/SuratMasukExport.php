<?php

namespace App\Exports;

use App\Models\SuratMasuk;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SuratMasukExport implements FromCollection, WithHeadings, WithStyles
{
    protected $dariTanggal;
    protected $sampaiTanggal;
    protected $klasifikasiId;

    public function __construct($dariTanggal, $sampaiTanggal, $klasifikasiId)
    {
        $this->dariTanggal = $dariTanggal ? Carbon::parse($dariTanggal) : Carbon::now()->startOfMonth();
        $this->sampaiTanggal = $sampaiTanggal ? Carbon::parse($sampaiTanggal) : Carbon::now()->endOfMonth();
        $this->klasifikasiId = $klasifikasiId;
    }

    public function collection()
    {
        $query = SuratMasuk::with(['klasifikasi', 'unitKerja', 'user'])
            ->whereBetween('tanggal_diterima', [$this->dariTanggal, $this->sampaiTanggal]);

        if ($this->klasifikasiId) {
            $query->where('klasifikasi_surat_id', $this->klasifikasiId);
        }

        return $query->latest('tanggal_diterima')
            ->get()
            ->map(function ($item) {
                return [
                    'Nomor Surat' => $item->nomor_surat,
                    'Pengirim' => $item->pengirim,
                    'Tanggal Diterima' => $item->tanggal_diterima->format('d-m-Y'),
                    'Perihal' => $item->perihal,
                    'Klasifikasi' => $item->klasifikasi->nama_klasifikasi ?? '-',
                    'Unit Kerja' => $item->unitKerja->nama_unit_kerja ?? '-',
                    'Status' => ucfirst($item->status),
                ];
            });
    }

    public function headings(): array
    {
        return [
            'Nomor Surat',
            'Pengirim',
            'Tanggal Diterima',
            'Perihal',
            'Klasifikasi',
            'Unit Kerja',
            'Status',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '4F46E5']],
            ],
        ];
    }
}
