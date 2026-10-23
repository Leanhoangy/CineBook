@extends('layouts.admin')

@section('title', 'Người dùng')
@section('subtitle', $users->total().' tài khoản')

@section('content')
    <div class="ad-card">
        <div class="ad-card-header">
            <form method="GET" class="d-flex flex-wrap gap-2">
                <input type="search" name="q" value="{{ request('q') }}" class="form-control form-control-sm" style="width: 230px" placeholder="Tên, email, số điện thoại...">
                <select name="role" class="form-select form-select-sm w-auto">
                    <option value="">Mọi vai trò</option>
                    @foreach (\App\Models\User::ROLES as $value => $label)
                        <option value="{{ $value }}" @selected(request('role') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <button class="btn btn-sm btn-light"><i class="bi bi-funnel me-1"></i>Lọc</button>
            </form>
            <a href="{{ route('admin.users.create') }}" class="btn btn-sm btn-primary"><i class="bi bi-person-plus me-1"></i>Thêm tài khoản</a>
        </div>
        <div class="table-responsive">
            <table class="table ad-table table-hover">
                <thead><tr><th>Người dùng</th><th>Số điện thoại</th><th>Vai trò</th><th class="text-center">Số đơn</th><th class="text-end">Chi tiêu</th><th>Ngày tạo</th><th class="text-end">Thao tác</th></tr></thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    @if ($user->avatar_url)
                                        <img src="{{ $user->avatar_url }}" alt="" class="ad-avatar">
                                    @else
                                        <span class="ad-avatar">{{ $user->initials }}</span>
                                    @endif
                                    <div>
                                        <div class="fw-semibold">{{ $user->name }} @if ($user->is(auth()->user()))<span class="badge text-bg-light">Bạn</span>@endif</div>
                                        <small class="text-muted">{{ $user->email }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>{{ $user->phone ?: '—' }}</td>
                            <td><x-status-badge :status="$user->role" /></td>
                            <td class="text-center">{{ $user->bookings_count }}</td>
                            <td class="text-end">@money($user->total_spent ?? 0)</td>
                            <td class="small">{{ $user->created_at->format('d/m/Y') }}</td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-light btn-icon" title="Sửa"><i class="bi bi-pencil"></i></a>
                                @unless ($user->is(auth()->user()))
                                    <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="d-inline" data-confirm="Xóa tài khoản {{ $user->email }}?">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-light btn-icon text-danger" title="Xóa"><i class="bi bi-trash"></i></button>
                                    </form>
                                @endunless
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="ad-empty"><i class="bi bi-people"></i>Không tìm thấy người dùng.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($users->hasPages())
            <div class="p-3 border-top">{{ $users->links() }}</div>
        @endif
    </div>
@endsection
