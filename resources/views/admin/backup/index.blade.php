@extends('layouts.app')

@section('title', 'Manajemen Backup')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="bi bi-cloud-arrow-down"></i> Manajemen Backup Data
            </h1>
            <p class="text-muted mt-2">Kelola backup database dan file sistem</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.backup.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> Backup Baru
            </a>
            <form action="{{ route('admin.backup.cleanup') }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-warning" 
                    onclick="return confirm('Yakin hapus backup lama? Backup lebih dari 7 hari akan dihapus.')">
                    <i class="bi bi-trash"></i> Bersihkan
                </button>
            </form>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-light h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted mb-1 small">Total Backup</p>
                            <h4 class="mb-0">{{ $stats['total_backups'] }}</h4>
                        </div>
                        <i class="bi bi-archive text-primary" style="font-size: 1.5rem;"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-light h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted mb-1 small">Total Ukuran</p>
                            <h4 class="mb-0">
                                @php
                                    $size = $stats['total_size'];
                                    $units = ['B', 'KB', 'MB', 'GB'];
                                    $size = max($size, 0);
                                    $pow = floor(($size ? log($size) : 0) / log(1024));
                                    $pow = min($pow, count($units) - 1);
                                    $size /= (1 << (10 * $pow));
                                    echo round($size, 2) . ' ' . $units[$pow];
                                @endphp
                            </h4>
                        </div>
                        <i class="bi bi-cloud text-success" style="font-size: 1.5rem;"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-light h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted mb-1 small">Backup Terakhir</p>
                            <h6 class="mb-0">
                                @if($stats['last_backup'])
                                    {{ $stats['last_backup']->backup_date->format('d M Y H:i') }}
                                @else
                                    <span class="text-muted">Belum ada</span>
                                @endif
                            </h6>
                        </div>
                        <i class="bi bi-calendar text-info" style="font-size: 1.5rem;"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-light h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted mb-1 small">Backup Gagal</p>
                            <h4 class="mb-0">{{ $stats['failed_backups'] }}</h4>
                        </div>
                        <i class="bi bi-exclamation-circle text-danger" style="font-size: 1.5rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Backup List -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-light border-bottom">
            <h5 class="mb-0"><i class="bi bi-list-ul"></i> Riwayat Backup</h5>
        </div>

        <!-- Desktop Table View -->
        <div class="d-none d-lg-block">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Nama File</th>
                            <th>Tipe Backup</th>
                            <th>Ukuran</th>
                            <th>Status</th>
                            <th>Tanggal Backup</th>
                            <th>Dibuat Oleh</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($backups as $backup)
                        <tr>
                            <td>
                                <small class="font-monospace">{{ Str::limit($backup->file_name, 40) }}</small>
                            </td>
                            <td>
                                @if($backup->backup_type == 'database')
                                    <span class="badge bg-info">Database</span>
                                @elseif($backup->backup_type == 'files')
                                    <span class="badge bg-warning">Files</span>
                                @else
                                    <span class="badge bg-primary">Full</span>
                                @endif
                            </td>
                            <td>{{ $backup->getHumanReadableSize() }}</td>
                            <td>
                                <span class="badge bg-{{ $backup->getStatusBadgeClass() }}">
                                    {{ $backup->getStatusLabel() }}
                                </span>
                            </td>
                            <td>
                                <small>{{ $backup->backup_date->format('d M Y H:i') }}</small>
                            </td>
                            <td>
                                <small>{{ $backup->user->name ?? 'System' }}</small>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm" role="group">
                                    @if($backup->status == 'completed')
                                    <a href="{{ route('admin.backup.download', $backup) }}" 
                                        class="btn btn-sm btn-outline-primary" title="Download">
                                        <i class="bi bi-download"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-outline-success" 
                                        data-bs-toggle="modal" data-bs-target="#restoreModal{{ $backup->id }}"
                                        title="Restore">
                                        <i class="bi bi-arrow-counterclockwise"></i>
                                    </button>
                                    @endif
                                    <form action="{{ route('admin.backup.destroy', $backup) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger"
                                            onclick="return confirm('Yakin hapus backup ini?')" title="Hapus">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">Belum ada backup</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Mobile Card View -->
        <div class="d-lg-none p-3">
            @forelse($backups as $backup)
            <div class="card shadow-sm border-0 mb-3">
                <div class="card-body">
                    <div class="mb-3 pb-3 border-bottom">
                        <h6 class="mb-2 text-truncate" title="{{ $backup->file_name }}">
                            <i class="bi bi-file-earmark"></i> {{ Str::limit($backup->file_name, 30) }}
                        </h6>
                        <p class="mb-0 small text-muted">
                            {{ $backup->backup_date->format('d M Y H:i') }}
                        </p>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <small class="text-muted d-block">Tipe</small>
                            @if($backup->backup_type == 'database')
                                <span class="badge bg-info">Database</span>
                            @elseif($backup->backup_type == 'files')
                                <span class="badge bg-warning">Files</span>
                            @else
                                <span class="badge bg-primary">Full</span>
                            @endif
                        </div>
                        <div class="col-6">
                            <small class="text-muted d-block">Ukuran</small>
                            <p class="mb-0 small">{{ $backup->getHumanReadableSize() }}</p>
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <small class="text-muted d-block">Status</small>
                            <span class="badge bg-{{ $backup->getStatusBadgeClass() }}">
                                {{ $backup->getStatusLabel() }}
                            </span>
                        </div>
                        <div class="col-6">
                            <small class="text-muted d-block">Dibuat Oleh</small>
                            <p class="mb-0 small">{{ $backup->user->name ?? 'System' }}</p>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        @if($backup->status == 'completed')
                        <a href="{{ route('admin.backup.download', $backup) }}" 
                            class="btn btn-sm btn-outline-primary flex-grow-1">
                            <i class="bi bi-download"></i> Download
                        </a>
                        <button type="button" class="btn btn-sm btn-outline-success flex-grow-1" 
                            data-bs-toggle="modal" data-bs-target="#restoreModal{{ $backup->id }}">
                            <i class="bi bi-arrow-counterclockwise"></i> Restore
                        </button>
                        @endif
                        <form action="{{ route('admin.backup.destroy', $backup) }}" method="POST" class="flex-grow-1">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger w-100"
                                onclick="return confirm('Yakin hapus backup ini?')">
                                <i class="bi bi-trash"></i> Hapus
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            @empty
            <div class="text-center text-muted py-4">
                <p>Belum ada backup</p>
            </div>
            @endforelse
        </div>

    <!-- Restore Modals (untuk Desktop dan Mobile) -->
    @foreach($backups as $backup)
    <div class="modal fade" id="restoreModal{{ $backup->id }}" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title">
                        <i class="bi bi-exclamation-triangle"></i> Restore Backup
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning mb-3">
                        <strong>⚠️ Peringatan!</strong>
                        <p class="mb-0 mt-2">Restore akan menimpa data saat ini dengan data dari backup tanggal {{ $backup->backup_date->format('d M Y H:i') }}. Pastikan Anda telah membuat backup terbaru sebelum melanjutkan.</p>
                    </div>
                    <form action="{{ route('admin.backup.restore', $backup) }}" method="POST">
                        @csrf
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="confirm" 
                                id="confirm{{ $backup->id }}" required>
                            <label class="form-check-label" for="confirm{{ $backup->id }}">
                                Ya, saya yakin untuk restore backup ini
                            </label>
                        </div>
                        <button type="submit" class="btn btn-warning w-100">
                            <i class="bi bi-arrow-counterclockwise"></i> Restore Sekarang
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endforeach

    <!-- Pagination -->
    @if($backups->hasPages())
    <div class="mt-4">
        {{ $backups->links() }}
    </div>
    @endif
</div>

<style>
    .table-hover tbody tr:hover {
        background-color: rgba(0, 123, 255, 0.05);
    }
</style>
@endsection
