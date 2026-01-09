@extends('layouts.app')

@section('title', 'Buat Backup Baru')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Header -->
            <div class="mb-4">
                <a href="{{ route('admin.backup.index') }}" class="btn btn-outline-secondary btn-sm mb-3">
                    <i class="bi bi-chevron-left"></i> Kembali
                </a>
                <h1 class="h3">
                    <i class="bi bi-cloud-arrow-down"></i> Buat Backup Baru
                </h1>
                <p class="text-muted">Pilih tipe backup yang ingin dibuat</p>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <form action="{{ route('admin.backup.store') }}" method="POST">
                        @csrf

                        <!-- Backup Type Selection -->
                        <div class="mb-4">
                            <label class="form-label fw-semibold mb-3">Tipe Backup</label>
                            <div class="row g-3">
                                <!-- Database Only -->
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="backup_type" 
                                            id="typeDatabase" value="database" checked>
                                        <label class="form-check-label w-100" for="typeDatabase">
                                            <div class="card h-100 cursor-pointer border-2" style="border-color: #e0e0e0;">
                                                <div class="card-body text-center">
                                                    <i class="bi bi-database" style="font-size: 2rem; color: #0066cc;"></i>
                                                    <h6 class="mt-3 mb-2">Database</h6>
                                                    <p class="text-muted small mb-0">Backup data persuratan saja</p>
                                                    <p class="text-success small mt-2">⚡ Cepat</p>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                </div>

                                <!-- Files Only -->
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="backup_type" 
                                            id="typeFiles" value="files">
                                        <label class="form-check-label w-100" for="typeFiles">
                                            <div class="card h-100 cursor-pointer border-2" style="border-color: #e0e0e0;">
                                                <div class="card-body text-center">
                                                    <i class="bi bi-folder2" style="font-size: 2rem; color: #ff9900;"></i>
                                                    <h6 class="mt-3 mb-2">File & Lampiran</h6>
                                                    <p class="text-muted small mb-0">Backup dokumen & file upload</p>
                                                    <p class="text-warning small mt-2">💾 Besar</p>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                </div>

                                <!-- Full Backup -->
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="backup_type" 
                                            id="typeFull" value="full">
                                        <label class="form-check-label w-100" for="typeFull">
                                            <div class="card h-100 cursor-pointer border-2" style="border-color: #e0e0e0;">
                                                <div class="card-body text-center">
                                                    <i class="bi bi-cloud-check" style="font-size: 2rem; color: #00cc00;"></i>
                                                    <h6 class="mt-3 mb-2">Full</h6>
                                                    <p class="text-muted small mb-0">Database + File & Lampiran</p>
                                                    <p class="text-info small mt-2">🔒 Lengkap</p>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Information -->
                        <div class="alert alert-info mb-4" role="alert">
                            <i class="bi bi-info-circle"></i>
                            <strong>Informasi:</strong>
                            <ul class="mb-0 mt-2 small">
                                <li><strong>Database</strong> - Berisi semua data surat masuk, keluar, disposisi, dll</li>
                                <li><strong>Files</strong> - Berisi file surat yang di-upload dan lampiran dokumen</li>
                                <li><strong>Full</strong> - Backup lengkap, bisa digunakan untuk migrasi server</li>
                            </ul>
                        </div>

                        <!-- Notes -->
                        <div class="mb-4">
                            <label for="notes" class="form-label">Catatan (Opsional)</label>
                            <textarea class="form-control @error('notes') is-invalid @enderror" 
                                id="notes" name="notes" rows="3" 
                                placeholder="Contoh: Backup sebelum update fitur baru...">{{ old('notes') }}</textarea>
                            @error('notes')
                            <div class="invalid-feedback d-block">
                                {{ $message }}
                            </div>
                            @enderror
                        </div>

                        <!-- Timeline Info -->
                        <div class="card bg-light border-0 mb-4">
                            <div class="card-body">
                                <h6 class="card-title">
                                    <i class="bi bi-clock-history"></i> Estimasi Waktu
                                </h6>
                                <p class="card-text mb-0 small">
                                    <span id="timeEstimate">Database: ~2-5 menit</span>
                                </p>
                            </div>
                        </div>

                        <!-- Buttons -->
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary flex-grow-1">
                                <i class="bi bi-download"></i> Mulai Backup
                            </button>
                            <a href="{{ route('admin.backup.index') }}" class="btn btn-outline-secondary">
                                Batal
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tips Section -->
            <div class="card border-0 shadow-sm mt-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="bi bi-lightbulb"></i> Tips Backup</h5>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled">
                        <li class="mb-3">
                            <strong>💡 Backup Harian:</strong> 
                            <span class="text-muted">Lakukan backup database setiap hari untuk keamanan data</span>
                        </li>
                        <li class="mb-3">
                            <strong>💡 Full Backup Mingguan:</strong> 
                            <span class="text-muted">Lakukan full backup setiap minggu untuk disaster recovery</span>
                        </li>
                        <li class="mb-3">
                            <strong>💡 Simpan di Tempat Aman:</strong> 
                            <span class="text-muted">Download backup dan simpan di drive eksternal atau cloud</span>
                        </li>
                        <li>
                            <strong>💡 Test Restore:</strong> 
                            <span class="text-muted">Secara berkala test restore backup untuk memastikan data dapat di-recover</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('input[name="backup_type"]').forEach(radio => {
    radio.addEventListener('change', function() {
        const timeEstimate = document.getElementById('timeEstimate');
        const estimates = {
            'database': 'Database: ~2-5 menit',
            'files': 'Files: ~5-15 menit (tergantung ukuran)',
            'full': 'Full Backup: ~10-30 menit'
        };
        timeEstimate.textContent = estimates[this.value];
    });
});

// Highlight selected option
document.querySelectorAll('.form-check-input').forEach(input => {
    input.addEventListener('change', function() {
        document.querySelectorAll('.card').forEach(card => {
            if (card.parentElement.querySelector('.form-check-input') === this) {
                card.style.borderColor = '#0066cc';
                card.style.backgroundColor = 'rgba(0, 102, 204, 0.05)';
            } else {
                card.style.borderColor = '#e0e0e0';
                card.style.backgroundColor = 'transparent';
            }
        });
    });
});
</script>

<style>
    .cursor-pointer {
        cursor: pointer;
    }

    .form-check-input:checked {
        background-color: #0066cc;
        border-color: #0066cc;
    }
</style>
@endsection
