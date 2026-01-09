<?php

namespace App\Http\Controllers;

use App\Models\SuratMasuk;
use App\Models\User;
use App\Models\Disposisi;
use App\Rules\UniqueNomorSurat;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class SuratMasukController extends Controller
{
    /**
     * Daftar surat masuk
     */
    public function index(): View
    {
        $query = SuratMasuk::with(['user'])
            ->latest('tanggal_diterima');

        // Jika ada pencarian
        if (request('search')) {
            $search = request('search');
            $query->where('nomor_surat', 'like', "%$search%")
                  ->orWhere('pengirim', 'like', "%$search%")
                  ->orWhere('perihal', 'like', "%$search%");
        }

        // Jika ada filter status
        if (request('status')) {
            $query->where('status', request('status'));
        }

        $suratMasuk = $query->paginate(10)->appends(request()->query());

        return view('surat_masuk.index', compact('suratMasuk'));
    }

    /**
     * Form tambah surat masuk
     */
    public function create(): View
    {
        return view('surat_masuk.create');
    }

    /**
     * Simpan surat masuk baru
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nomor_surat' => [
                'required',
                new UniqueNomorSurat('surat_masuk')
            ],
            'tanggal_surat' => 'required|date',
            'tanggal_diterima' => 'required|date',
            'pengirim' => 'required|string',
            'perihal' => 'required|string',
            'disposisi_ke' => 'required|array|min:1',
            'disposisi_ke.*' => 'string',
            'isi_disposisikan' => 'required|array|min:1',
            'isi_disposisikan.*' => 'string',
            'jenis_surat' => 'required|in:Biasa,Penting,Rahasia',
            'catatan' => 'nullable|string',
            'file_surat' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'jumlah_lampiran' => 'nullable|integer|min:0'
        ]);

        // Convert array to comma-separated string for storage
        $validated['disposisi_ke'] = implode(',', $request->input('disposisi_ke', []));
        $validated['isi_disposisikan'] = implode(',', $request->input('isi_disposisikan', []));
        $validated['user_id'] = auth()->id();

        if ($request->hasFile('file_surat')) {
            $file = $request->file('file_surat');
            $originalName = $file->getClientOriginalName();
            $path = $file->storeAs('surat_masuk', $originalName, 'public');
            $validated['file_surat'] = $path;
        }

        $suratMasuk = SuratMasuk::create($validated);

        // Buat Disposisi otomatis - fleksibel tanpa verifikasi user
        try {
            Disposisi::create([
                'surat_masuk_id' => $suratMasuk->id,
                'diteruskan_ke' => $validated['disposisi_ke'],
                'instruksi' => 'Surat dari ' . $validated['pengirim'] . ' - ' . $validated['perihal'],
                'status' => 'baru'
            ]);
        } catch (\Exception $e) {
            // Jika ada error, tetap lanjutkan (surat masuk tetap tersimpan)
            \Log::warning('Error creating disposisi: ' . $e->getMessage());
        }

        return redirect()->route('surat-masuk.index')
            ->with('success', 'Surat masuk berhasil disimpan');
    }

    /**
     * Tampilkan detail surat masuk
     */
    public function show(SuratMasuk $suratMasuk): View
    {
        // Jika surat berjenis "Rahasia" dan user bukan Admin/Tata Usaha, tolak akses
        if (strtolower($suratMasuk->jenis_surat) === 'rahasia' && 
            !auth()->user()->isAdmin() && 
            !auth()->user()->isTataUsaha()) {
            abort(403, 'Anda tidak memiliki akses untuk melihat surat rahasia ini.');
        }

        $suratMasuk->load(['user', 'disposisi']);

        return view('surat_masuk.show', compact('suratMasuk'));
    }

    /**
     * Form edit surat masuk
     */
    public function edit(SuratMasuk $suratMasuk): View
    {
        return view('surat_masuk.edit', compact('suratMasuk'));
    }

    /**
     * Update surat masuk
     */
    public function update(Request $request, SuratMasuk $suratMasuk): RedirectResponse
    {
        $validated = $request->validate([
            'nomor_surat' => [
                'required',
                new UniqueNomorSurat('surat_masuk', $suratMasuk->id)
            ],
            'tanggal_surat' => 'required|date',
            'tanggal_diterima' => 'required|date',
            'pengirim' => 'required|string',
            'perihal' => 'required|string',
            'disposisi_ke' => 'required|array|min:1',
            'disposisi_ke.*' => 'string',
            'isi_disposisikan' => 'required|array|min:1',
            'isi_disposisikan.*' => 'string',
            'jenis_surat' => 'required|in:Biasa,Penting,Rahasia',
            'catatan' => 'nullable|string',
            'file_surat' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'jumlah_lampiran' => 'nullable|integer|min:0',
            'status' => 'required|in:diterima,diproses,selesai'
        ]);

        // Convert array to comma-separated string for storage
        $validated['disposisi_ke'] = implode(',', $request->input('disposisi_ke', []));
        $validated['isi_disposisikan'] = implode(',', $request->input('isi_disposisikan', []));

if ($request->hasFile('file_surat')) {
            if ($suratMasuk->file_surat) {
                Storage::disk('public')->delete($suratMasuk->file_surat);
            }
            $file = $request->file('file_surat');
            $originalName = $file->getClientOriginalName();
            $path = $file->storeAs('surat_masuk', $originalName, 'public');
            $validated['file_surat'] = $path;
        }

        $suratMasuk->update($validated);

        return redirect()->route('surat-masuk.show', $suratMasuk)
            ->with('success', 'Surat masuk berhasil diupdate');
    }

    /**
     * Hapus surat masuk
     */
    public function destroy(SuratMasuk $suratMasuk): RedirectResponse
    {
        if ($suratMasuk->file_surat) {
            Storage::disk('public')->delete($suratMasuk->file_surat);
        }

        $suratMasuk->delete();

        return redirect()->route('surat-masuk.index')
            ->with('success', 'Surat masuk berhasil dihapus');
    }

    /**
     * Update status surat masuk
     */
    public function updateStatus(Request $request, SuratMasuk $suratMasuk): RedirectResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:diterima,diproses,selesai',
            'catatan' => 'nullable|string'
        ]);

        $suratMasuk->update($validated);

        return back()->with('success', 'Status surat berhasil diperbarui menjadi ' . ucfirst($validated['status']));
    }

    /**
     * Download file surat
     */
    public function downloadFile(SuratMasuk $suratMasuk)
    {
        if (!$suratMasuk->file_surat || !Storage::disk('public')->exists($suratMasuk->file_surat)) {
            abort(404, 'File tidak ditemukan');
        }

        // Jika request dari preview (Accept header untuk inline), serve dengan inline
        // Jika request dari download button, serve dengan download
        $filePath = Storage::disk('public')->path($suratMasuk->file_surat);
        $mimeType = Storage::disk('public')->mimeType($suratMasuk->file_surat);
        $fileName = basename($suratMasuk->file_surat);
        
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
    public function generateShare(SuratMasuk $suratMasuk): RedirectResponse
    {
        // Check if user is admin or tata usaha
        if (!auth()->user()->isAdmin() && !auth()->user()->isTataUsaha()) {
            return back()->with('error', 'Anda tidak memiliki izin untuk membagikan surat ini.');
        }
        
        if (!$suratMasuk->share_token) {
            $suratMasuk->generateShareToken();
        }

        return back()->with('success', 'Link berbagi telah dibuat. Anda dapat membagikannya kepada orang lain.');
    }

    /**
     * Revoke share link
     */
    public function revokeShare(SuratMasuk $suratMasuk): RedirectResponse
    {
        // Check if user is admin or tata usaha
        if (!auth()->user()->isAdmin() && !auth()->user()->isTataUsaha()) {
            return back()->with('error', 'Anda tidak memiliki izin untuk membatalkan berbagi surat ini.');
        }
        
        $suratMasuk->revokeShare();

        return back()->with('success', 'Link berbagi telah dibatalkan.');
    }

    /**
     * View surat masuk dengan share token (public access)
     */
    public function viewShared($shareToken)
    {
        $suratMasuk = SuratMasuk::where('share_token', $shareToken)
            ->where('is_shared', true)
            ->with(['user', 'disposisi'])
            ->firstOrFail();

        return view('surat_masuk.shared', compact('suratMasuk'));
    }

    /**
     * Download file dengan share token (public access)
     */
    public function downloadShared($shareToken)
    {
        $suratMasuk = SuratMasuk::where('share_token', $shareToken)
            ->where('is_shared', true)
            ->firstOrFail();

        if (!$suratMasuk->file_surat || !Storage::disk('public')->exists($suratMasuk->file_surat)) {
            abort(404, 'File tidak ditemukan');
        }

        return Storage::disk('public')->download($suratMasuk->file_surat);
    }

    /**
     * Preview file share untuk akses LAN
     */
    public function previewShared($shareToken)
    {
        $suratMasuk = SuratMasuk::where('share_token', $shareToken)
            ->where('is_shared', true)
            ->firstOrFail();

        if (!$suratMasuk->file_surat || !Storage::disk('public')->exists($suratMasuk->file_surat)) {
            abort(404, 'File tidak ditemukan');
        }

        $filePath = Storage::disk('public')->path($suratMasuk->file_surat);
        $mimeType = Storage::disk('public')->mimeType($suratMasuk->file_surat);

        return response()->file($filePath, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . basename($suratMasuk->file_surat) . '"',
        ]);
    }

    /**
     * Hapus semua surat masuk (dengan filter jika ada)
     */
    public function deleteAll(Request $request): RedirectResponse
    {
        $query = SuratMasuk::query();

        // Apply filters dari form jika ada
        if ($request->get('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('nomor_surat', 'like', "%$search%")
                  ->orWhere('pengirim', 'like', "%$search%")
                  ->orWhere('perihal', 'like', "%$search%");
            });
        }

        if ($request->get('status')) {
            $query->where('status', $request->get('status'));
        }

        // Get all surat to delete
        $suratMasuk = $query->get();
        $count = $suratMasuk->count();

        // Delete files and records
        foreach ($suratMasuk as $surat) {
            if ($surat->file_surat) {
                Storage::disk('public')->delete($surat->file_surat);
            }
            $surat->delete();
        }

        if ($count > 0) {
            return redirect()->route('surat-masuk.index')
                ->with('success', "$count surat masuk berhasil dihapus");
        } else {
            return back()->with('warning', 'Tidak ada surat yang dihapus');
        }
    }
}