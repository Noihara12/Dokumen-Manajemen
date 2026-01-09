<?php

namespace App\Http\Controllers;

use App\Models\SuratMasuk;
use App\Models\SuratKeluar;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Tampilkan dashboard
     */
    public function index(): View
    {
        $bulanIni = Carbon::now()->month;
        $tahunIni = Carbon::now()->year;

        // Statistik
        $totalSuratMasuk = SuratMasuk::count();
        $totalSuratKeluar = SuratKeluar::count();
        $suratMasukBulanIni = SuratMasuk::whereMonth('tanggal_diterima', $bulanIni)
            ->whereYear('tanggal_diterima', $tahunIni)
            ->count();
        $suratKeluarBulanIni = SuratKeluar::whereMonth('tanggal_surat', $bulanIni)
            ->whereYear('tanggal_surat', $tahunIni)
            ->count();

        // Surat Terbaru
        $suratMasukTerbaru = SuratMasuk::with(['user'])
            ->orderBy('tanggal_diterima', 'desc')
            ->limit(5)
            ->get();

        $suratKeluarTerbaru = SuratKeluar::with(['user'])
            ->orderBy('tanggal_surat', 'desc')
            ->limit(5)
            ->get();

        // Data Grafik - 6 bulan terakhir
        $grafikMasuk = $this->getGrafikSuratMasuk();
        $grafikKeluar = $this->getGrafikSuratKeluar();

        return view('dashboard.index', compact(
            'totalSuratMasuk',
            'totalSuratKeluar',
            'suratMasukBulanIni',
            'suratKeluarBulanIni',
            'suratMasukTerbaru',
            'suratKeluarTerbaru',
            'grafikMasuk',
            'grafikKeluar'
        ));
    }

    /**
     * Get data grafik surat masuk 6 bulan terakhir
     */
    private function getGrafikSuratMasuk()
    {
        $data = [];
        $labels = [];

        for ($i = 5; $i >= 0; $i--) {
            $bulan = Carbon::now()->subMonths($i);
            $labels[] = $bulan->format('M Y');
            
            $count = SuratMasuk::whereMonth('tanggal_diterima', $bulan->month)
                ->whereYear('tanggal_diterima', $bulan->year)
                ->count();
            $data[] = $count;
        }

        return ['labels' => $labels, 'data' => $data];
    }

    /**
     * Get data grafik surat keluar 6 bulan terakhir
     */
    private function getGrafikSuratKeluar()
    {
        $data = [];
        $labels = [];

        for ($i = 5; $i >= 0; $i--) {
            $bulan = Carbon::now()->subMonths($i);
            $labels[] = $bulan->format('M Y');
            
            $count = SuratKeluar::whereMonth('tanggal_surat', $bulan->month)
                ->whereYear('tanggal_surat', $bulan->year)
                ->count();
            $data[] = $count;
        }

        return ['labels' => $labels, 'data' => $data];
    }
}
