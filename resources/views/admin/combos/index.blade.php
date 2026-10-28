@extends('layouts.admin')

@section('title', 'Bắp nước')
@section('subtitle', 'Combo bắp nước khách đặt kèm vé, nhận tại quầy khi soát vé')

@section('content')
    <div class="ad-card">
        <div class="ad-card-header">
            <h2>{{ $combos->count() }} món</h2>
            <a href="{{ route('admin.combos.create') }}" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg me-1"></i>Thêm combo</a>
        </div>
        <div class="table-responsive">
            <table class="table ad-table table-hover">
                <thead><tr><th>Combo</th><th class="text-end">Giá bán</th><th class="text-center">Đã bán</th><th class="text-end">Doanh thu</th><th>Trạng thái</th><th class="text-end">Thao tác</th></tr></thead>
                <tbody>
                    @forelse ($combos as $combo)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    @if ($combo->image_url)
                                        <img src="{{ $combo->image_url }}" alt="" class="ad-combo-thumb">
                                    @else
                                        <span class="ad-combo-thumb">🍿</span>
                                    @endif
                                    <div>
                                        <div class="fw-semibold">{{ $combo->name }}</div>
                                        <small class="text-muted">{{ $combo->description }}</small>
                                    </div>
                                </div>
                            </td>
                            <td class="text-end text-nowrap fw-semibold">@money($combo->price)</td>
                            <td class="text-center">{{ (int) $combo->sold }}</td>
                            <td class="text-end text-nowrap">@money($combo->revenue ?? 0)</td>
                            <td>
                                <span class="badge rounded-pill {{ $combo->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $combo->is_active ? 'Đang bán' : 'Ngừng bán' }}</span>
                            </td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('admin.combos.edit', $combo) }}" class="btn btn-sm btn-light btn-icon" title="Sửa"><i class="bi bi-pencil"></i></a>
                                <form method="POST" action="{{ route('admin.combos.destroy', $combo) }}" class="d-inline" data-confirm="Xóa {{ $combo->name }}?">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-light btn-icon text-danger" title="Xóa"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="ad-empty"><i class="bi bi-cup-straw"></i>Chưa có combo bắp nước nào.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
