@include('partials.form-errors')

<div class="ad-card p-4" style="max-width: 760px">
    <div class="row g-3">
        <div class="col-md-5">
            <label for="code" class="form-label">Mã <span class="text-danger">*</span></label>
            <input type="text" id="code" name="code" value="{{ old('code', $voucher->code) }}" maxlength="30" class="form-control text-uppercase font-monospace @error('code') is-invalid @enderror" placeholder="VD: CINEBOOK10" autofocus>
            @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-7">
            <label for="description" class="form-label">Mô tả</label>
            <input type="text" id="description" name="description" value="{{ old('description', $voucher->description) }}" class="form-control @error('description') is-invalid @enderror" placeholder="VD: Ưu đãi cho thành viên mới">
            @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-4">
            <label for="type" class="form-label">Loại giảm giá <span class="text-danger">*</span></label>
            <select id="type" name="type" class="form-select @error('type') is-invalid @enderror">
                @foreach (\App\Models\Voucher::TYPES as $value => $label)
                    <option value="{{ $value }}" @selected(old('type', $voucher->type) === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-4">
            <label for="value" class="form-label">Giá trị giảm <span class="text-danger">*</span> <span class="text-muted small" id="valueUnit"></span></label>
            <input type="number" id="value" name="value" min="1" value="{{ old('value', $voucher->value) }}" class="form-control @error('value') is-invalid @enderror">
            @error('value') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-4" id="maxDiscountGroup">
            <label for="max_discount" class="form-label">Giảm tối đa (₫)</label>
            <input type="number" id="max_discount" name="max_discount" min="1000" step="1000" value="{{ old('max_discount', $voucher->max_discount) }}" class="form-control @error('max_discount') is-invalid @enderror" placeholder="Không giới hạn">
            @error('max_discount') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-6">
            <label for="min_order" class="form-label">Đơn tối thiểu (₫)</label>
            <input type="number" id="min_order" name="min_order" min="0" step="1000" value="{{ old('min_order', $voucher->min_order) }}" class="form-control @error('min_order') is-invalid @enderror" placeholder="0">
            @error('min_order') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-6">
            <label for="usage_limit" class="form-label">Tổng số lượt dùng</label>
            <input type="number" id="usage_limit" name="usage_limit" min="1" value="{{ old('usage_limit', $voucher->usage_limit) }}" class="form-control @error('usage_limit') is-invalid @enderror" placeholder="Không giới hạn">
            @error('usage_limit') <div class="invalid-feedback">{{ $message }}</div> @enderror
            <div class="form-text">Mỗi khách chỉ dùng mỗi mã 1 lần. Vé bị hủy sẽ được hoàn lượt.</div>
        </div>

        <div class="col-md-6">
            <label for="starts_at" class="form-label">Bắt đầu</label>
            <input type="datetime-local" id="starts_at" name="starts_at" value="{{ old('starts_at', $voucher->starts_at?->format('Y-m-d\TH:i')) }}" class="form-control @error('starts_at') is-invalid @enderror">
            @error('starts_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-6">
            <label for="expires_at" class="form-label">Hết hạn</label>
            <input type="datetime-local" id="expires_at" name="expires_at" value="{{ old('expires_at', $voucher->expires_at?->format('Y-m-d\TH:i')) }}" class="form-control @error('expires_at') is-invalid @enderror">
            @error('expires_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-12">
            <div class="form-check form-switch">
                <input type="hidden" name="is_active" value="0">
                <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1" @checked(old('is_active', $voucher->is_active))>
                <label class="form-check-label" for="is_active">Đang áp dụng</label>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2 mt-4">
        <button class="btn btn-primary px-4"><i class="bi bi-check-lg me-1"></i>Lưu</button>
        <a href="{{ route('admin.vouchers.index') }}" class="btn btn-light">Hủy</a>
    </div>
</div>

@push('scripts')
    <script>
        // Mã giảm theo % mới có "Giảm tối đa"; đổi đơn vị hiển thị theo loại
        (() => {
            const type = document.getElementById('type');
            const sync = () => {
                const percent = type.value === 'percent';
                document.getElementById('maxDiscountGroup').classList.toggle('d-none', !percent);
                document.getElementById('max_discount').disabled = !percent;
                document.getElementById('valueUnit').textContent = percent ? '(%)' : '(₫)';
            };
            type.addEventListener('change', sync);
            sync();
        })();
    </script>
@endpush
