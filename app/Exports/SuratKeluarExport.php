<?php

namespace App\Exports;

use App\Models\SuratKeluar;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SuratKeluarExport implements FromCollection, WithHeadings, WithStyles
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
        $query = SuratKeluar::with(['klasifikasi', 'unitKerja', 'user'])
            ->whereBetween('tanggal_surat', [$this->dariTanggal, $this->sampaiTanggal])
            ->where('status', '!=', 'draft');

        if ($this->klasifikasiId) {
            $query->where('klasifikasi_surat_id', $this->klasifikasiId);
        }

        return $query->latest('tanggal_surat')
            ->get()
            ->map(function ($item) {
                return [
                    'Nomor Surat' => $item->nomor_surat,
                    'Tujuan' => $item->tujuan,
                    'Tanggal Surat' => $item->tanggal_surat->format('d-m-Y'),
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
            'Tujuan',
            'Tanggal Surat',
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
