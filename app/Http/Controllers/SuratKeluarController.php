<?php

namespace App\Http\Controllers;

use App\Models\SuratKeluar;
use App\Rules\UniqueNomorSurat;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class SuratKeluarController extends Controller
{
    /**
     * Daftar surat keluar
     */
    public function index(): View
    {
        $query = SuratKeluar::with(['user'])
            ->latest('tanggal_surat');

        // Jika ada pencarian
        if (request('search')) {
            $search = request('search');
            $query->where('nomor_surat', 'like', "%$search%")
                  ->orWhere('tujuan', 'like', "%$search%")
                  ->orWhere('perihal', 'like', "%$search%");
        }

        // Jika ada filter status
        if (request('status')) {
            $query->where('status', request('status'));
        }

        $suratKeluar = $query->paginate(10)->appends(request()->query());

        return view('surat_keluar.index', compact('suratKeluar'));
    }

    /**
     * Form tambah surat keluar
     */
    public function create(): View
    {
        return view('surat_keluar.create');
    }

    /**
     * Generate nomor surat otomatis
     */
    private function generateNomorSurat()
    {
        $tahun = Carbon::now()->year;
        $bulan = str_pad(Carbon::now()->month, 2, '0', STR_PAD_LEFT);
        
        // Get all non-deleted surat keluar for this month
        $surats = SuratKeluar::whereYear('created_at', $tahun)
            ->whereMonth('created_at', Carbon::now()->month)
            ->withoutTrashed()
            ->pluck('nomor_surat')
            ->toArray();

        // Find the highest number from nomor_surat format XXX/SK/MM/YYYY
        $numbers = array_map(function($nomor) {
            return intval(explode('/', $nomor)[0]);
        }, $surats);

        $urutan = !empty($numbers) ? max($numbers) + 1 : 1;
        
        return str_pad($urutan, 3, '0', STR_PAD_LEFT) . '/' . strtoupper('SK') . '/' . $bulan . '/' . $tahun;
    }

    /**
     * Simpan surat keluar baru
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nomor_surat' => [
                'required',
                new UniqueNomorSurat('surat_keluar')
            ],
            'tanggal_surat' => 'required|date',
            'tujuan' => 'required|string',
            'perihal' => 'required|string',
            'catatan' => 'nullable|string',
            'file_surat' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120'
        ]);

        $validated['user_id'] = auth()->id();
        $validated['status'] = 'draft';

if ($request->hasFile('file_surat')) {
            $file = $request->file('file_surat');
            $originalName = $file->getClientOriginalName();
            $path = $file->storeAs('surat_keluar', $originalName, 'public');
            $validated['file_surat'] = $path;
        }

        SuratKeluar::create($validated);

        return redirect()->route('surat-keluar.index')
            ->with('success', 'Surat keluar berhasil disimpan');
    }

    /**
     * Tampilkan detail surat keluar
     */
    public function show(SuratKeluar $suratKeluar): View
    {
        $suratKeluar->load(['user']);

        return view('surat_keluar.show', compact('suratKeluar'));
    }

    /**
     * Form edit surat keluar
     */
    public function edit(SuratKeluar $suratKeluar): View
    {
        // Cek otorisasi
        if (!auth()->user()->can('edit-surat-keluar')) {
            abort(403, 'Anda tidak memiliki akses untuk edit surat keluar');
        }

        if ($suratKeluar->status !== 'draft') {
            return back()->with('error', 'Hanya surat dengan status draft yang dapat diedit');
        }

        return view('surat_keluar.edit', compact('suratKeluar'));
    }

    /**
     * Update surat keluar
     */
    public function update(Request $request, SuratKeluar $suratKeluar): RedirectResponse
    {
        // Cek otorisasi
        if (!auth()->user()->can('edit-surat-keluar')) {
            abort(403, 'Anda tidak memiliki akses untuk edit surat keluar');
        }

        if ($suratKeluar->status !== 'draft') {
            return back()->with('error', 'Hanya surat dengan status draft yang dapat diedit');
        }

        $validated = $request->validate([
            'tanggal_surat' => 'required|date',
            'tujuan' => 'required|string',
            'perihal' => 'required|string',
            'catatan' => 'nullable|string',
            'file_surat' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120'
        ]);

        if ($request->hasFile('file_surat')) {
            if ($suratKeluar->file_surat) {
                Storage::disk('public')->delete($suratKeluar->file_surat);
            }
            $file = $request->file('file_surat');
            $originalName = $file->getClientOriginalName();
            $path = $file->storeAs('surat_keluar', $originalName, 'public');
            $validated['file_surat'] = $path;
        }

        $suratKeluar->update($validated);

        return redirect()->route('surat-keluar.show', $suratKeluar)
            ->with('success', 'Surat keluar berhasil diupdate');
    }

    /**
     * Ubah status ke dikirim
     */
    public function send(SuratKeluar $suratKeluar): RedirectResponse
    {
        // Cek otorisasi (hanya Admin & Tata Usaha)
        if (!auth()->user()->isAdmin() && !auth()->user()->isTataUsaha()) {
            abort(403, 'Anda tidak memiliki akses untuk mengirim surat keluar');
        }

        if ($suratKeluar->status !== 'draft') {
            return back()->with('error', 'Hanya surat dengan status draft yang dapat dikirim');
        }

        $suratKeluar->update([
            'status' => 'dikirim',
            'tanggal_pengiriman' => now()
        ]);

        return back()->with('success', 'Surat keluar berhasil dikirim');
    }

    /**
     * Arsipkan surat keluar
     */
    public function archive(SuratKeluar $suratKeluar): RedirectResponse
    {
        // Cek otorisasi (hanya Admin & Tata Usaha)
        if (!auth()->user()->isAdmin() && !auth()->user()->isTataUsaha()) {
            abort(403, 'Anda tidak memiliki akses untuk mengarsipkan surat keluar');
        }

        $suratKeluar->update(['status' => 'arsip']);

        return back()->with('success', 'Surat keluar berhasil diarsipkan');
    }

    /**
     * Update status surat keluar
     */
    public function updateStatus(Request $request, SuratKeluar $suratKeluar): RedirectResponse
    {
        // Cek otorisasi (hanya Admin & Tata Usaha)
        if (!auth()->user()->isAdmin() && !auth()->user()->isTataUsaha()) {
            abort(403, 'Anda tidak memiliki akses untuk mengubah status surat keluar');
        }

        $validated = $request->validate([
            'status' => 'required|in:draft,dikirim,arsip'
        ]);

        // Jika status berubah ke dikirim, set tanggal pengiriman
        if ($validated['status'] == 'dikirim' && $suratKeluar->status != 'dikirim') {
            $validated['tanggal_pengiriman'] = now();
        }

        $suratKeluar->update($validated);

        return back()->with('success', 'Status surat berhasil diperbarui menjadi ' . ucfirst($validated['status']));
    }

    /**
     * Hapus surat keluar
     */
    public function destroy(SuratKeluar $suratKeluar): RedirectResponse
    {
        // Cek otorisasi (hanya Admin & Tata Usaha)
        if (!auth()->user()->isAdmin() && !auth()->user()->isTataUsaha()) {
            abort(403, 'Anda tidak memiliki akses untuk menghapus surat keluar');
        }

        if ($suratKeluar->file_surat) {
            Storage::disk('public')->delete($suratKeluar->file_surat);
        }

        $suratKeluar->delete();

        return redirect()->route('surat-keluar.index')
            ->with('success', 'Surat keluar berhasil dihapus');
    }

    /**
     * Download file surat
     */
    public function downloadFile(SuratKeluar $suratKeluar)
    {
        if (!$suratKeluar->file_surat || !Storage::disk('public')->exists($suratKeluar->file_surat)) {
            abort(404, 'File tidak ditemukan');
        }

        // Jika request dari preview (Accept header untuk inline), serve dengan inline
        // Jika request dari download button, serve dengan download
        $filePath = Storage::disk('public')->path($suratKeluar->file_surat);
        $mimeType = Storage::disk('public')->mimeType($suratKeluar->file_surat);
        $fileName = basename($suratKeluar->file_surat);
        
        // Check if this is a preview request (User-Agent atau Accept header)
        $disposition = 'attachment'; // Default: download
        
        // Jika dari preview modal atau browser request, gunakan inline
        if (request()->has('preview') || 
            strpos(request()->header('Accept', ''), 'text/html') !== false ||
            request()->expectsJson() === false) {
            $disposition = 'inline';
        }

        return response()->file($filePath, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => "$disposition; filename=\"$fileName\"",
        ]);
    }

    /**
     * Generate share link
     */
    public function generateShare(SuratKeluar $suratKeluar): RedirectResponse
    {
        // Check if user is admin or tata usaha
        if (!auth()->user()->isAdmin() && !auth()->user()->isTataUsaha()) {
            return back()->with('error', 'Anda tidak memiliki izin untuk membagikan surat ini.');
        }
        
        if (!$suratKeluar->share_token) {
            $suratKeluar->generateShareToken();
        }

        return back()->with('success', 'Link berbagi telah dibuat. Anda dapat membagikannya kepada orang lain.');
    }

    /**
     * Revoke share link
     */
    public function revokeShare(SuratKeluar $suratKeluar): RedirectResponse
    {
        // Check if user is admin or tata usaha
        if (!auth()->user()->isAdmin() && !auth()->user()->isTataUsaha()) {
            return back()->with('error', 'Anda tidak memiliki izin untuk membatalkan berbagi surat ini.');
        }
        
        $suratKeluar->revokeShare();

        return back()->with('success', 'Link berbagi telah dibatalkan.');
    }

    /**
     * View surat keluar dengan share token (public access)
     */
    public function viewShared($shareToken)
    {
        $suratKeluar = SuratKeluar::where('share_token', $shareToken)
            ->where('is_shared', true)
            ->with(['user'])
            ->firstOrFail();

        return view('surat_keluar.shared', compact('suratKeluar'));
    }

    /**
     * Download file dengan share token (public access)
     */
    public function downloadShared($shareToken)
    {
        $suratKeluar = SuratKeluar::where('share_token', $shareToken)
            ->where('is_shared', true)
            ->firstOrFail();

        if (!$suratKeluar->file_surat || !Storage::disk('public')->exists($suratKeluar->file_surat)) {
            abort(404, 'File tidak ditemukan');
        }

        return Storage::disk('public')->download($suratKeluar->file_surat);
    }

    /**
     * Preview file share untuk akses LAN
     */
    public function previewShared($shareToken)
    {
        $suratKeluar = SuratKeluar::where('share_token', $shareToken)
            ->where('is_shared', true)
            ->firstOrFail();

        if (!$suratKeluar->file_surat || !Storage::disk('public')->exists($suratKeluar->file_surat)) {
            abort(404, 'File tidak ditemukan');
        }

        $filePath = Storage::disk('public')->path($suratKeluar->file_surat);
        $mimeType = Storage::disk('public')->mimeType($suratKeluar->file_surat);

        return response()->file($filePath, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . basename($suratKeluar->file_surat) . '"',
        ]);
    }

    /**
     * Hapus semua surat keluar (dengan filter jika ada)
     */
    public function deleteAll(Request $request): RedirectResponse
    {
        $query = SuratKeluar::query();

        // Apply filters dari form jika ada
        if ($request->get('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('nomor_surat', 'like', "%$search%")
                  ->orWhere('tujuan', 'like', "%$search%")
                  ->orWhere('perihal', 'like', "%$search%");
            });
        }

        // Get all surat to delete
        $suratKeluar = $query->get();
        $count = $suratKeluar->count();

        // Delete files and records
        foreach ($suratKeluar as $surat) {
            if ($surat->file_surat) {
                Storage::disk('public')->delete($surat->file_surat);
            }
            $surat->delete();
        }

        if ($count > 0) {
            return redirect()->route('surat-keluar.index')
                ->with('success', "$count surat keluar berhasil dihapus");
        } else {
            return back()->with('warning', 'Tidak ada surat yang dihapus');
        }
    }
}