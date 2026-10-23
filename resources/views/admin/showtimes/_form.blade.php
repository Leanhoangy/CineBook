@include('partials.form-errors')

<div class="ad-card p-4" style="max-width: 720px">
    <div class="row g-3">
        <div class="col-12">
            <label for="movie_id" class="form-label">Phim <span class="text-danger">*</span></label>
            <select id="movie_id" name="movie_id" class="form-select @error('movie_id') is-invalid @enderror">
                <option value="">— Chọn phim —</option>
                @foreach ($movies as $movie)
                    <option value="{{ $movie->id }}" data-duration="{{ $movie->duration }}" @selected(old('movie_id', $showtime->movie_id) == $movie->id)>
                        {{ $movie->title }} ({{ $movie->duration }} phút{{ $movie->status === 'coming_soon' ? ', sắp chiếu' : '' }})
                    </option>
                @endforeach
            </select>
            @error('movie_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-6">
            <label for="room_id" class="form-label">Phòng chiếu <span class="text-danger">*</span></label>
            <select id="room_id" name="room_id" class="form-select @error('room_id') is-invalid @enderror">
                <option value="">— Chọn phòng —</option>
                @foreach ($rooms as $room)
                    <option value="{{ $room->id }}" @selected(old('room_id', $showtime->room_id) == $room->id)>{{ $room->name }} ({{ $room->capacity }} ghế)</option>
                @endforeach
            </select>
            @error('room_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-6">
            <label for="price" class="form-label">Giá vé thường (₫) <span class="text-danger">*</span></label>
            <input type="number" id="price" name="price" step="1000" min="30000" value="{{ old('price', $showtime->price) }}" class="form-control @error('price') is-invalid @enderror">
            <div class="form-text">Ghế VIP tự động cộng thêm @money(config('cinema.vip_surcharge')).</div>
            @error('price') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-6">
            <label for="start_time" class="form-label">Giờ bắt đầu <span class="text-danger">*</span></label>
            <input type="datetime-local" id="start_time" name="start_time" value="{{ old('start_time', $showtime->start_time?->format('Y-m-d\TH:i')) }}" class="form-control @error('start_time') is-invalid @enderror">
            @error('start_time') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-6">
            <label class="form-label">Giờ kết thúc (dự kiến)</label>
            <input type="text" id="end_preview" class="form-control bg-light" readonly>
            <div class="form-text">= giờ bắt đầu + thời lượng phim + {{ config('cinema.cleaning_minutes') }} phút dọn phòng.</div>
        </div>
    </div>
    <div class="d-flex gap-2 mt-4">
        <button class="btn btn-primary px-4"><i class="bi bi-check-lg me-1"></i>Lưu</button>
        <a href="{{ route('admin.showtimes.index') }}" class="btn btn-light">Hủy</a>
    </div>
</div>

@push('scripts')
    <script>
        const cleaning = {{ (int) config('cinema.cleaning_minutes') }};
        const updateEnd = () => {
            const opt = document.getElementById('movie_id').selectedOptions[0];
            const start = document.getElementById('start_time').value;
            const out = document.getElementById('end_preview');
            if (!opt?.dataset.duration || !start) { out.value = ''; return; }
            const end = new Date(new Date(start).getTime() + (+opt.dataset.duration + cleaning) * 60000);
            out.value = end.toLocaleString('vi-VN', { hour: '2-digit', minute: '2-digit', day: '2-digit', month: '2-digit', year: 'numeric' });
        };
        ['movie_id', 'start_time'].forEach(id => document.getElementById(id).addEventListener('input', updateEnd));
        updateEnd();
    </script>
@endpush
