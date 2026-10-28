@include('partials.form-errors')

<div class="ad-card p-4" style="max-width: 760px">
    <div class="row g-3">
        <div class="col-md-8">
            <div class="mb-3">
                <label for="name" class="form-label">Tên combo <span class="text-danger">*</span></label>
                <input type="text" id="name" name="name" value="{{ old('name', $combo->name) }}" maxlength="100" class="form-control @error('name') is-invalid @enderror" placeholder="VD: Combo Couple" autofocus>
                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Thành phần</label>
                <input type="text" id="description" name="description" value="{{ old('description', $combo->description) }}" maxlength="255" class="form-control @error('description') is-invalid @enderror" placeholder="VD: 1 bắp rang bơ (L) + 2 nước ngọt (L)">
                @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="mb-3">
                <label for="price" class="form-label">Giá bán (₫) <span class="text-danger">*</span></label>
                <input type="number" id="price" name="price" min="1000" step="1000" value="{{ old('price', $combo->price) }}" class="form-control @error('price') is-invalid @enderror">
                @error('price') <div class="invalid-feedback">{{ $message }}</div> @enderror
                <div class="form-text">Đổi giá không ảnh hưởng các đơn đã đặt (đơn lưu giá tại thời điểm mua).</div>
            </div>
            <div class="form-check form-switch">
                <input type="hidden" name="is_active" value="0">
                <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1" @checked(old('is_active', $combo->is_active))>
                <label class="form-check-label" for="is_active">Đang bán</label>
            </div>
        </div>
        <div class="col-md-4">
            <label for="image" class="form-label d-block">Ảnh</label>
            <div class="mb-2">
                <img id="comboPreview" @if ($combo->image_url) src="{{ $combo->image_url }}" @endif alt="" class="ad-combo-preview {{ $combo->image_url ? '' : 'd-none' }}">
                @unless ($combo->image_url)
                    <div id="comboEmpty" class="ad-combo-preview d-flex align-items-center justify-content-center fs-1">🍿</div>
                @endunless
            </div>
            <input type="file" id="image" name="image" accept="image/*" class="form-control form-control-sm @error('image') is-invalid @enderror" data-preview="#comboPreview" data-hide="#comboEmpty">
            @error('image') <div class="invalid-feedback">{{ $message }}</div> @enderror
            <div class="form-text">JPG, PNG, WEBP ≤ 2MB. Không có ảnh sẽ hiện biểu tượng 🍿.</div>
            @if ($combo->image)
                <div class="form-check mt-2">
                    <input type="hidden" name="remove_image" value="0">
                    <input class="form-check-input" type="checkbox" name="remove_image" value="1" id="remove_image">
                    <label class="form-check-label small" for="remove_image">Xóa ảnh hiện tại</label>
                </div>
            @endif
        </div>
    </div>

    <div class="d-flex gap-2 mt-4">
        <button class="btn btn-primary px-4"><i class="bi bi-check-lg me-1"></i>Lưu</button>
        <a href="{{ route('admin.combos.index') }}" class="btn btn-light">Hủy</a>
    </div>
</div>
