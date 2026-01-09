<?php

namespace App\Http\Controllers;

use App\Models\SuratMasuk;
use App\Models\SuratKeluar;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class LaporanController extends Controller
{
    /**
     * Laporan surat masuk
     */
    public function suratMasuk(): View
    {
        $dari = request('dari_tanggal') ? Carbon::parse(request('dari_tanggal')) : Carbon::now()->startOfYear();
        $sampai = request('sampai_tanggal') ? Carbon::parse(request('sampai_tanggal')) : Carbon::now()->endOfYear();

        $query = SuratMasuk::with(['user'])
            ->whereBetween('tanggal_diterima', [$dari, $sampai->endOfDay()]);

        $suratMasuk = $query->latest('tanggal_diterima')->get();
        $totalSurat = $suratMasuk->count();
        $diterima = $suratMasuk->where('status', 'diterima')->count();
        $diproses = $suratMasuk->where('status', 'diproses')->count();
        $selesai = $suratMasuk->where('status', 'selesai')->count();

        return view('laporan.surat-masuk', compact('suratMasuk', 'dari', 'sampai', 'totalSurat', 'diterima', 'diproses', 'selesai'));
    }

    /**
     * Laporan surat keluar
     */
    public function suratKeluar(): View
    {
        $dari = request('dari_tanggal') ? Carbon::parse(request('dari_tanggal')) : Carbon::now()->startOfYear();
        $sampai = request('sampai_tanggal') ? Carbon::parse(request('sampai_tanggal')) : Carbon::now()->endOfYear();

        $query = SuratKeluar::with(['user'])
            ->whereBetween('tanggal_surat', [$dari, $sampai->endOfDay()])
            ->where('status', '!=', 'draft');

        $suratKeluar = $query->latest('tanggal_surat')->get();
        $totalSurat = $suratKeluar->count();
        $draft = SuratKeluar::whereBetween('tanggal_surat', [$dari, $sampai->endOfDay()])->where('status', 'draft')->count();
        $dikirim = $suratKeluar->where('status', 'dikirim')->count();
        $arsip = $suratKeluar->where('status', 'arsip')->count();

        return view('laporan.surat-keluar', compact('suratKeluar', 'dari', 'sampai', 'totalSurat', 'draft', 'dikirim', 'arsip'));
    }

    /**
     * Export PDF surat masuk
     */
    public function exportPdfSuratMasuk()
    {
        $dari = request('dari_tanggal') ? Carbon::parse(request('dari_tanggal')) : Carbon::now()->startOfYear();
        $sampai = request('sampai_tanggal') ? Carbon::parse(request('sampai_tanggal')) : Carbon::now()->endOfYear();

        $query = SuratMasuk::with(['user'])
            ->whereBetween('tanggal_diterima', [$dari, $sampai->endOfDay()]);

        $suratMasuk = $query->latest('tanggal_diterima')->get();

        $pdf = Pdf::loadView('laporan.export.surat-masuk-pdf', compact('suratMasuk', 'dari', 'sampai'));
        return $pdf->download('Laporan-Surat-Masuk-' . now()->format('Y-m-d') . '.pdf');
    }

    /**
     * Export PDF surat keluar
     */
    public function exportPdfSuratKeluar()
    {
        $dari = request('dari_tanggal') ? Carbon::parse(request('dari_tanggal')) : Carbon::now()->startOfYear();
        $sampai = request('sampai_tanggal') ? Carbon::parse(request('sampai_tanggal')) : Carbon::now()->endOfYear();

        $query = SuratKeluar::with(['user'])
            ->whereBetween('tanggal_surat', [$dari, $sampai->endOfDay()])
            ->where('status', '!=', 'draft');

        $suratKeluar = $query->latest('tanggal_surat')->get();

        $pdf = Pdf::loadView('laporan.export.surat-keluar-pdf', compact('suratKeluar', 'dari', 'sampai'));
        return $pdf->download('Laporan-Surat-Keluar-' . now()->format('Y-m-d') . '.pdf');
    }
}
