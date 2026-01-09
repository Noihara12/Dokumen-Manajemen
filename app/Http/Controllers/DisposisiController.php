<?php

namespace App\Http\Controllers;

use App\Models\Disposisi;
use App\Models\SuratMasuk;
use App\Models\User;
use App\Models\UnitKerja;
use App\Models\RiwayatDisposisi;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class DisposisiController extends Controller
{
    /**
     * Daftar disposisi
     */
    public function index(): View
    {
        // Semua user bisa lihat disposisi
        $query = Disposisi::with(['suratMasuk']);

        // Filter berdasarkan nomor surat
        if (request('nomor_surat')) {
            $query->whereHas('suratMasuk', function($q) {
                $q->where('nomor_surat', 'like', '%' . request('nomor_surat') . '%');
            });
        }

        // Filter berdasarkan diteruskan ke
        if (request('diteruskan_ke')) {
            $query->where('diteruskan_ke', request('diteruskan_ke'));
        }

        // Filter berdasarkan status
        if (request('status')) {
            $query->where('status', request('status'));
        }

        $disposisi = $query->latest('created_at')
            ->paginate(10)
            ->appends(request()->query());

        // Ambil daftar unit kerja yang unik dari disposisi
        $unitKerjaRaw = Disposisi::distinct()
            ->where('diteruskan_ke', '!=', null)
            ->orderBy('diteruskan_ke')
            ->pluck('diteruskan_ke');

        // Kelompokkan berdasarkan kata pertama
        $unitKerjaList = [];
        foreach ($unitKerjaRaw as $unit) {
            $parts = explode(' ', $unit);
            $group = $parts[0] ?? 'Lainnya';
            if (!isset($unitKerjaList[$group])) {
                $unitKerjaList[$group] = [];
            }
            $unitKerjaList[$group][] = $unit;
        }

        // Daftar status
        $statusList = ['baru', 'diproses', 'selesai'];

        return view('disposisi.index', compact('disposisi', 'unitKerjaList', 'statusList'));
    }

    /**
     * Disposisi Saya - Disposisi yang ditujukan ke unit kerja user
     */
    public function disposisiSaya(): View
    {
        $user = auth()->user();
        
        // Jika user belum punya unit kerja, tampilkan pesan
        if (!$user->unit_kerja_id) {
            return view('disposisi.saya', [
                'disposisi' => collect(),
                'unitKerja' => null,
                'hasUnitKerja' => false
            ]);
        }

        $unitKerja = $user->unitKerja;

        // Ambil disposisi yang ditujukan ke unit kerja user
        $query = Disposisi::with(['suratMasuk', 'unitKerja'])
            ->where('unit_kerja_id', $user->unit_kerja_id);

        // Filter berdasarkan nomor surat
        if (request('nomor_surat')) {
            $query->whereHas('suratMasuk', function($q) {
                $q->where('nomor_surat', 'like', '%' . request('nomor_surat') . '%');
            });
        }

        // Filter berdasarkan status
        if (request('status')) {
            $query->where('status', request('status'));
        }

        $disposisi = $query->latest('created_at')
            ->paginate(10)
            ->appends(request()->query());

        $statusList = ['baru', 'diproses', 'selesai'];

        return view('disposisi.saya', compact('disposisi', 'unitKerja', 'statusList'));
    }

    /**
     * Form tambah disposisi (dari surat masuk)
     */
    public function create(SuratMasuk $suratMasuk): View
    {
        if (!auth()->user()->isTataUsaha() && !auth()->user()->isAdmin()) {
            abort(403, 'Hanya TU yang dapat membuat disposisi');
        }

        $unitKerja = UnitKerja::where('is_active', true)->orderBy('nama_unit')->get();

        return view('disposisi.create', compact('suratMasuk', 'unitKerja'));
    }

    /**
     * Form tambah multiple disposisi (dari surat masuk)
     */
    public function createMultiple(SuratMasuk $suratMasuk): View
    {
        if (!auth()->user()->isTataUsaha() && !auth()->user()->isAdmin()) {
            abort(403, 'Hanya TU yang dapat membuat disposisi');
        }

        $unitKerja = UnitKerja::where('is_active', true)->orderBy('nama_unit')->get();

        return view('disposisi.create-multiple', compact('suratMasuk', 'unitKerja'));
    }

    /**
     * Simpan disposisi baru (single)
     */
    public function store(Request $request): RedirectResponse
    {
        if (!auth()->user()->isTataUsaha() && !auth()->user()->isAdmin()) {
            abort(403, 'Hanya TU yang dapat membuat disposisi');
        }

        $validated = $request->validate([
            'surat_masuk_id' => 'required|exists:surat_masuk,id',
            'unit_kerja_id' => 'required|exists:unit_kerja,id',
            'instruksi' => 'required|string|min:10',
            'batas_waktu' => 'nullable|date|after:today',
            'catatan' => 'nullable|string'
        ]);

        $dataDisposisi = [
            'surat_masuk_id' => $validated['surat_masuk_id'],
            'unit_kerja_id' => $validated['unit_kerja_id'],
            'instruksi' => $validated['instruksi'],
            'status' => 'baru'
        ];

        if (!empty($validated['batas_waktu'])) {
            $dataDisposisi['batas_waktu'] = $validated['batas_waktu'];
        }

        if (!empty($validated['catatan'])) {
            $dataDisposisi['catatan'] = $validated['catatan'];
        }

        $disposisi = Disposisi::create($dataDisposisi);

        // Catat riwayat disposisi
        RiwayatDisposisi::create([
            'disposisi_id' => $disposisi->id,
            'user_id' => auth()->id(),
            'aksi' => 'diteruskan',
            'keterangan' => 'Disposisi dibuat oleh ' . auth()->user()->name,
            'waktu_aksi' => now()
        ]);

        return redirect()->route('surat-masuk.show', $disposisi->surat_masuk_id)
            ->with('success', 'Disposisi berhasil dibuat');
    }

    /**
     * Simpan multiple disposisi sekaligus
     */
    public function storeMultiple(Request $request): RedirectResponse
    {
        if (!auth()->user()->isTataUsaha() && !auth()->user()->isAdmin()) {
            abort(403, 'Hanya TU yang dapat membuat disposisi');
        }

        $validated = $request->validate([
            'surat_masuk_id' => 'required|exists:surat_masuk,id',
            'disposisi' => 'required|array|min:1',
            'disposisi.*.unit_kerja_id' => 'required|exists:unit_kerja,id',
            'disposisi.*.instruksi' => 'required|string|min:10',
            'disposisi.*.batas_waktu' => 'nullable|date|after:today',
            'disposisi.*.catatan' => 'nullable|string'
        ], [
            'disposisi.required' => 'Minimal harus ada 1 disposisi',
            'disposisi.*.unit_kerja_id.required' => 'Kolom "Unit Kerja" harus dipilih',
            'disposisi.*.unit_kerja_id.exists' => 'Unit kerja yang dipilih tidak valid',
            'disposisi.*.instruksi.required' => 'Kolom "Instruksi" harus diisi',
            'disposisi.*.instruksi.min' => 'Instruksi minimal 10 karakter'
        ]);

        $suratMasukId = $validated['surat_masuk_id'];
        $successCount = 0;
        $errorCount = 0;

        try {
            foreach ($validated['disposisi'] as $disposisiData) {
                try {
                    $dataDisposisi = [
                        'surat_masuk_id' => $suratMasukId,
                        'unit_kerja_id' => $disposisiData['unit_kerja_id'],
                        'instruksi' => $disposisiData['instruksi'],
                        'status' => 'baru'
                    ];

                    if (!empty($disposisiData['batas_waktu'])) {
                        $dataDisposisi['batas_waktu'] = $disposisiData['batas_waktu'];
                    }

                    if (!empty($disposisiData['catatan'])) {
                        $dataDisposisi['catatan'] = $disposisiData['catatan'];
                    }

                    $disposisi = Disposisi::create($dataDisposisi);

                    // Catat riwayat disposisi
                    RiwayatDisposisi::create([
                        'disposisi_id' => $disposisi->id,
                        'user_id' => auth()->id(),
                        'aksi' => 'diteruskan',
                        'keterangan' => 'Disposisi dibuat oleh ' . auth()->user()->name,
                        'waktu_aksi' => now()
                    ]);

                    $successCount++;
                } catch (\Exception $e) {
                    $errorCount++;
                    \Log::error('Error creating disposisi: ' . $e->getMessage());
                }
            }

            $message = "Berhasil membuat $successCount disposisi";
            if ($errorCount > 0) {
                $message .= ", gagal membuat $errorCount disposisi";
            }

            return redirect()->route('surat-masuk.show', $suratMasukId)
                ->with('success', $message);
        } catch (\Exception $e) {
            \Log::error('Error in storeMultiple: ' . $e->getMessage());
            return back()
                ->with('error', 'Terjadi kesalahan saat membuat disposisi: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Tampilkan detail disposisi
     */
    public function show(Disposisi $disposisi): View
    {
        $disposisi->load(['suratMasuk', 'riwayat']);

        return view('disposisi.show', compact('disposisi'));
    }

    /**
     * Form edit disposisi
     */
    public function edit(Disposisi $disposisi)
    {
        if (!auth()->user()->isTataUsaha() && !auth()->user()->isAdmin()) {
            abort(403, 'Hanya TU yang dapat edit disposisi');
        }

        if ($disposisi->status !== 'baru') {
            return back()->with('error', 'Hanya disposisi dengan status baru yang dapat diedit');
        }

        $unitKerja = UnitKerja::where('is_active', true)->orderBy('nama_unit')->get();

        return view('disposisi.edit', compact('disposisi', 'unitKerja'));
    }

    /**
     * Update disposisi
     */
    public function update(Request $request, Disposisi $disposisi): RedirectResponse
    {
        if (!auth()->user()->isTataUsaha() && !auth()->user()->isAdmin()) {
            abort(403, 'Hanya TU yang dapat update disposisi');
        }

        if ($disposisi->status !== 'baru') {
            return back()->with('error', 'Hanya disposisi dengan status baru yang dapat diedit');
        }

        $validated = $request->validate([
            'unit_kerja_id' => 'required|exists:unit_kerja,id',
            'instruksi' => 'required|string|min:10',
            'batas_waktu' => 'nullable|date|after:today',
            'catatan' => 'nullable|string'
        ]);

        $dataDisposisi = [
            'unit_kerja_id' => $validated['unit_kerja_id'],
            'instruksi' => $validated['instruksi']
        ];

        if (!empty($validated['batas_waktu'])) {
            $dataDisposisi['batas_waktu'] = $validated['batas_waktu'];
        }

        if (!empty($validated['catatan'])) {
            $dataDisposisi['catatan'] = $validated['catatan'];
        }

        $disposisi->update($dataDisposisi);

        return back()->with('success', 'Disposisi berhasil diupdate');
    }

    /**
     * Ubah status disposisi
     */
    public function updateStatus(Request $request, Disposisi $disposisi): RedirectResponse
    {
        if (!auth()->user()->isTataUsaha() && !auth()->user()->isAdmin()) {
            abort(403, 'Hanya TU yang dapat update status disposisi');
        }

        $validated = $request->validate([
            'status' => 'required|in:baru,diproses,selesai',
            'keterangan' => 'nullable|string'
        ]);

        $disposisi->update(['status' => $validated['status']]);

        // Catat riwayat
        RiwayatDisposisi::create([
            'disposisi_id' => $disposisi->id,
            'user_id' => auth()->id(),
            'aksi' => match($validated['status']) {
                'diproses' => 'diteruskan',
                'selesai' => 'diselesaikan',
                default => 'diteruskan'
            },
            'keterangan' => $validated['keterangan'] ?? 'Status diubah menjadi ' . $validated['status'],
            'waktu_aksi' => now()
        ]);

        return back()->with('success', 'Status disposisi berhasil diupdate');
    }

    /**
     * Hapus disposisi
     */
    public function destroy(Disposisi $disposisi): RedirectResponse
    {
        if (!auth()->user()->isTataUsaha() && !auth()->user()->isAdmin()) {
            abort(403, 'Hanya TU yang dapat hapus disposisi');
        }

        $suratMasukId = $disposisi->surat_masuk_id;
        $disposisi->delete();

        return redirect()->route('surat-masuk.show', $suratMasukId)
            ->with('success', 'Disposisi berhasil dihapus');
    }
}
