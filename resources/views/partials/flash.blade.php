{{-- Thông báo phản hồi sau khi thêm/sửa/xóa (flash message) --}}
<div class="cb-toast-wrap">
    @foreach (['success' => ['bg-success', 'bi-check-circle-fill'], 'error' => ['bg-danger', 'bi-exclamation-octagon-fill'], 'info' => ['bg-primary', 'bi-info-circle-fill']] as $key => [$bg, $icon])
        @if (session($key))
            <div class="toast align-items-center text-white {{ $bg }} border-0 mb-2" role="alert" aria-live="assertive" data-bs-delay="5000">
                <div class="d-flex">
                    <div class="toast-body d-flex gap-2 align-items-start">
                        <i class="bi {{ $icon }} mt-1"></i>
                        <span>{{ session($key) }}</span>
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Đóng"></button>
                </div>
            </div>
        @endif
    @endforeach
</div>
