@extends('layouts.admin')

@section('title', 'Phòng chiếu')

@section('content')
    <div class="ad-card">
        <div class="ad-card-header">
            <form method="GET" class="d-flex gap-2">
                <input type="search" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Tìm phòng...">
                <button class="btn btn-sm btn-light"><i class="bi bi-search"></i></button>
            </form>
            <a href="{{ route('admin.rooms.create') }}" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg me-1"></i>Thêm phòng</a>
        </div>
        <div class="table-responsive">
            <table class="table ad-table table-hover">
                <thead><tr><th>Tên phòng</th><th>Sơ đồ</th><th class="text-center">Sức chứa</th><th class="text-center">Hàng VIP</th><th class="text-center">Suất sắp tới</th><th>Trạng thái</th><th class="text-end">Thao tác</th></tr></thead>
                <tbody>
                    @forelse ($rooms as $room)
                        <tr>
                            <td class="fw-semibold">{{ $room->name }}</td>
                            <td class="text-muted">{{ $room->total_rows }} hàng × {{ $room->seats_per_row }} ghế</td>
                            <td class="text-center">{{ $room->capacity }}</td>
                            <td class="text-center">{{ $room->vip_rows }}</td>
                            <td class="text-center">{{ $room->upcoming_count }}</td>
                            <td>
                                @if ($room->is_active)
                                    <span class="badge rounded-pill text-bg-success">Hoạt động</span>
                                @else
                                    <span class="badge rounded-pill text-bg-secondary">Tạm ngưng</span>
                                @endif
                            </td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('admin.rooms.edit', $room) }}" class="btn btn-sm btn-light btn-icon" title="Sửa"><i class="bi bi-pencil"></i></a>
                                <form method="POST" action="{{ route('admin.rooms.destroy', $room) }}" class="d-inline" data-confirm="Xóa {{ $room->name }}?">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-light btn-icon text-danger" title="Xóa"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="ad-empty"><i class="bi bi-door-open"></i>Chưa có phòng chiếu.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($rooms->hasPages())
            <div class="p-3 border-top">{{ $rooms->links() }}</div>
        @endif
    </div>
@endsection
