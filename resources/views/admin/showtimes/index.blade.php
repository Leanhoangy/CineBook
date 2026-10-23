@extends('layouts.admin')

@section('title', 'Suất chiếu')
@section('subtitle', request('date') ? 'Ngày '.\Illuminate\Support\Carbon::parse(request('date'))->format('d/m/Y') : (request()->boolean('past') ? 'Tất cả suất chiếu' : 'Từ hôm nay trở đi'))

@section('content')
    <div class="ad-card">
        <div class="ad-card-header">
            <form method="GET" class="d-flex flex-wrap gap-2">
                <input type="search" name="q" value="{{ request('q') }}" class="form-control form-control-sm" style="width: 200px" placeholder="Tên phim...">
                <select name="room" class="form-select form-select-sm w-auto">
                    <option value="">Mọi phòng</option>
                    @foreach ($rooms as $room)
                        <option value="{{ $room->id }}" @selected(request('room') == $room->id)>{{ $room->name }}</option>
                    @endforeach
                </select>
                <input type="date" name="date" value="{{ request('date') }}" class="form-control form-control-sm w-auto">
                <div class="form-check align-self-center ms-1">
                    <input class="form-check-input" type="checkbox" name="past" value="1" id="past" @checked(request()->boolean('past'))>
                    <label class="form-check-label small" for="past">Gồm suất đã chiếu</label>
                </div>
                <button class="btn btn-sm btn-light"><i class="bi bi-funnel me-1"></i>Lọc</button>
                @if (request()->hasAny(['q', 'room', 'date', 'past']))
                    <a href="{{ route('admin.showtimes.index') }}" class="btn btn-sm btn-link text-muted">Xóa lọc</a>
                @endif
            </form>
            <a href="{{ route('admin.showtimes.create') }}" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg me-1"></i>Thêm suất chiếu</a>
        </div>
        <div class="table-responsive">
            <table class="table ad-table table-hover">
                <thead><tr><th>Phim</th><th>Phòng</th><th>Bắt đầu</th><th>Kết thúc</th><th class="text-end">Giá vé</th><th class="text-center">Đơn</th><th class="text-end">Thao tác</th></tr></thead>
                <tbody>
                    @forelse ($showtimes as $st)
                        @php $locked = $st->start_time->isPast() || $st->bookings_count > 0; @endphp
                        <tr class="{{ $st->start_time->isPast() ? 'text-muted' : '' }}">
                            <td class="fw-semibold">{{ $st->movie->title }} @if ($st->movie->trashed())<span class="badge text-bg-secondary">đã xóa</span>@endif</td>
                            <td>{{ $st->room->name }}</td>
                            <td class="text-nowrap">{{ $st->start_time->format('H:i') }} <small class="text-muted">{{ $st->start_time->format('d/m/Y') }}</small></td>
                            <td class="text-nowrap">{{ $st->end_time->format('H:i') }}</td>
                            <td class="text-end">@money($st->price)</td>
                            <td class="text-center">{{ $st->bookings_count }}</td>
                            <td class="text-end text-nowrap">
                                @if ($locked)
                                    <span class="text-muted small" title="Suất đã chiếu hoặc đã có vé nên không thể sửa/xóa"><i class="bi bi-lock"></i></span>
                                @else
                                    <a href="{{ route('admin.showtimes.edit', $st) }}" class="btn btn-sm btn-light btn-icon" title="Sửa"><i class="bi bi-pencil"></i></a>
                                    <form method="POST" action="{{ route('admin.showtimes.destroy', $st) }}" class="d-inline" data-confirm="Xóa suất chiếu {{ $st->start_time->format('H:i d/m') }}?">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-light btn-icon text-danger" title="Xóa"><i class="bi bi-trash"></i></button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="ad-empty"><i class="bi bi-calendar-x"></i>Không có suất chiếu phù hợp.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($showtimes->hasPages())
            <div class="p-3 border-top">{{ $showtimes->links() }}</div>
        @endif
    </div>
@endsection
