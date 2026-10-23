@include('partials.form-errors')

<div class="row g-4">
    <div class="col-lg-6">
        <div class="ad-card p-4">
            <div class="mb-3">
                <label for="name" class="form-label">Tên phòng <span class="text-danger">*</span></label>
                <input type="text" id="name" name="name" value="{{ old('name', $room->name) }}" class="form-control @error('name') is-invalid @enderror">
                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="row g-3 mb-3">
                <div class="col-4">
                    <label for="total_rows" class="form-label">Số hàng</label>
                    <input type="number" id="total_rows" name="total_rows" min="3" max="{{ \App\Models\Room::MAX_ROWS }}" value="{{ old('total_rows', $room->total_rows) }}" class="form-control @error('total_rows') is-invalid @enderror" data-seat-input>
                    @error('total_rows') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-4">
                    <label for="seats_per_row" class="form-label">Ghế/hàng</label>
                    <input type="number" id="seats_per_row" name="seats_per_row" min="4" max="{{ \App\Models\Room::MAX_SEATS_PER_ROW }}" value="{{ old('seats_per_row', $room->seats_per_row) }}" class="form-control @error('seats_per_row') is-invalid @enderror" data-seat-input>
                    @error('seats_per_row') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-4">
                    <label for="vip_rows" class="form-label">Hàng VIP</label>
                    <input type="number" id="vip_rows" name="vip_rows" min="0" value="{{ old('vip_rows', $room->vip_rows) }}" class="form-control @error('vip_rows') is-invalid @enderror" data-seat-input>
                    @error('vip_rows') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
            <div class="form-check form-switch mb-4">
                <input type="hidden" name="is_active" value="0">
                <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1" @checked(old('is_active', $room->is_active))>
                <label class="form-check-label" for="is_active">Phòng đang hoạt động</label>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-primary px-4"><i class="bi bi-check-lg me-1"></i>Lưu</button>
                <a href="{{ route('admin.rooms.index') }}" class="btn btn-light">Hủy</a>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="ad-card p-4 text-center">
            <h2 class="h6 fw-bold mb-3">Xem trước sơ đồ ghế</h2>
            <div class="small text-muted mb-2 border-top border-3 border-danger pt-1 mx-auto" style="max-width: 260px">MÀN HÌNH</div>
            <div class="ad-seat-preview" id="seatPreview"></div>
            <p class="small text-muted mt-3 mb-0"><span id="capacityText"></span> · <i class="d-inline-block align-middle" style="width:14px;height:12px;background:#f5b942;border-radius:3px"></i> VIP (+@money(config('cinema.vip_surcharge')))</p>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        // Vẽ lại sơ đồ ghế mỗi khi thay đổi số hàng/số ghế
        const drawSeats = () => {
            const rows = Math.min(+document.getElementById('total_rows').value || 0, 15);
            const cols = Math.min(+document.getElementById('seats_per_row').value || 0, 20);
            const vip = +document.getElementById('vip_rows').value || 0;
            let html = '';
            for (let r = 0; r < rows; r++) {
                html += '<div class="row-s">' + '<i class="' + (r >= rows - vip ? 'vip' : '') + '"></i>'.repeat(cols) + '</div>';
            }
            document.getElementById('seatPreview').innerHTML = html;
            document.getElementById('capacityText').textContent = `${rows * cols} ghế`;
        };
        document.querySelectorAll('[data-seat-input]').forEach(el => el.addEventListener('input', drawSeats));
        drawSeats();
    </script>
@endpush
