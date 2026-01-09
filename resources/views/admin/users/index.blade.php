@extends('layouts.app')

@section('title', 'Manajemen User')

@section('content')
<div class="row mb-5">
    <div class="col-md-9">
        <h1 class="h3">
            <i class="bi bi-people"></i> Manajemen User
        </h1>
    </div>
    <div class="col-md-3 text-end">
        <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Tambah User
        </a>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.users.index') }}" class="row g-3">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control" placeholder="Cari nama, email, atau username..." value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search"></i> Cari
                </button>
            </div>
            @if (request('search'))
                <div class="col-md-2">
                    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary w-100">
                        <i class="bi bi-arrow-clockwise"></i> Reset
                    </a>
                </div>
            @endif
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <!-- Desktop Table View -->
        <div class="table-responsive d-none d-lg-block">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Nama</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Unit Kerja</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr>
                            <td><strong>{{ $user->name }}</strong></td>
                            <td>
                                <code>{{ $user->username }}</code>
                            </td>
                            <td>{{ $user->email }}</td>
                            <td>
                                @if($user->unitKerja)
                                    <span class="badge bg-primary">{{ $user->unitKerja->nama_unit }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-{{ $user->role?->name === 'tata_usaha' ? 'success' : 'info' }}">
                                    {{ $user->role?->label ?? '-' }}
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-{{ $user->is_active ? 'success' : 'danger' }}">
                                    {{ $user->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td>
                                <div class="d-flex gap-2">
                                    <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-warning">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                    <form action="{{ route('admin.users.destroy', $user) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus user ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <i class="bi bi-trash"></i> Hapus
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted">Belum ada data user</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Mobile Card View -->
        <div class="d-lg-none">
            @forelse ($users as $user)
                <div class="card shadow-sm border-0 mb-3">
                    <div class="card-body">
                        <div class="mb-3 pb-3 border-bottom">
                            <h6 class="mb-2"><strong>{{ $user->name }}</strong></h6>
                            <p class="mb-1 small text-muted">
                                <i class="bi bi-at"></i> <code>{{ $user->username }}</code>
                            </p>
                            <p class="mb-0 small text-muted">
                                <i class="bi bi-envelope"></i> {{ $user->email }}
                            </p>
                        </div>

                        <div class="mb-3">
                            <div class="row g-2 mb-2">
                                <div class="col-6">
                                    <small class="text-muted d-block">Jabatan</small>
                                    <p class="mb-0 small">{{ $user->jabatan ?? '-' }}</p>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted d-block">Role</small>
                                    <span class="badge bg-{{ $user->role?->name === 'tata_usaha' ? 'success' : 'info' }}">
                                        {{ $user->role?->label ?? '-' }}
                                    </span>
                                </div>
                            </div>
                            <div class="row g-2">
                                <div class="col-6">
                                    <small class="text-muted d-block">Status</small>
                                    <span class="badge bg-{{ $user->is_active ? 'success' : 'danger' }}">
                                        {{ $user->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-2 flex-wrap">
                            <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-warning flex-shrink-0">
                                <i class="bi bi-pencil"></i> Edit
                            </a>
                            <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="flex-shrink-0" onsubmit="return confirm('Yakin ingin menghapus user ini?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger">
                                    <i class="bi bi-trash"></i> Hapus
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center text-muted py-4">
                    <p>Belum ada data user</p>
                </div>
            @endforelse
        </div>
        
        @if ($users->hasPages())
            <nav aria-label="Page navigation" class="mt-4">
                {{ $users->links() }}
            </nav>
        @endif
    </div>
</div>

@endsection
