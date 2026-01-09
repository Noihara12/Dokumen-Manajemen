<?php

namespace App\Http\Controllers;

use App\Models\SuratMasuk;
use App\Models\SuratKeluar;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ArsipController extends Controller
{
    /**
     * Arsip surat masuk
     */
    public function suratMasuk(): View
    {
        $query = SuratMasuk::with(['user']);

        // Filter berdasarkan tanggal
        if (request('dari_tanggal') && request('sampai_tanggal')) {
            $query->whereBetween('tanggal_diterima', [
                request('dari_tanggal'),
                request('sampai_tanggal')
            ]);
        }

        // Filter berdasarkan jenis surat
        if (request('jenis_surat')) {
            $query->where('jenis_surat', 'like', '%' . request('jenis_surat') . '%');
        }

        // Filter berdasarkan pengirim
        if (request('pengirim')) {
            $query->where('pengirim', 'like', '%' . request('pengirim') . '%');
        }

        // Pencarian
        if (request('search')) {
            $search = request('search');
            $query->where(function($q) use ($search) {
                $q->where('nomor_surat', 'like', '%' . $search . '%')
                    ->orWhere('perihal', 'like', '%' . $search . '%')
                    ->orWhere('pengirim', 'like', '%' . $search . '%');
            });
        }

        $suratMasuk = $query->latest('tanggal_diterima')->paginate(15);

        return view('arsip.surat-masuk', compact('suratMasuk'));
    }

    /**
     * Arsip surat keluar
     */
    public function suratKeluar(): View
    {
        $query = SuratKeluar::with(['user']);

        // Filter hanya surat keluar yang sudah diarsipkan
        $query->where('status', 'arsip');

        // Filter berdasarkan tanggal
        if (request('dari_tanggal') && request('sampai_tanggal')) {
            $query->whereBetween('tanggal_surat', [
                request('dari_tanggal'),
                request('sampai_tanggal')
            ]);
        }

        // Filter berdasarkan tujuan
        if (request('tujuan')) {
            $query->where('tujuan', 'like', '%' . request('tujuan') . '%');
        }

        // Pencarian
        if (request('search')) {
            $search = request('search');
            $query->where(function($q) use ($search) {
                $q->where('nomor_surat', 'like', '%' . $search . '%')
                    ->orWhere('perihal', 'like', '%' . $search . '%')
                    ->orWhere('tujuan', 'like', '%' . $search . '%');
            });
        }

        $suratKeluar = $query->latest('tanggal_surat')->paginate(15);

        return view('arsip.surat-keluar', compact('suratKeluar'));
    }
}
