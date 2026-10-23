@include('partials.form-errors')

<div class="mb-3">
    <label for="name" class="form-label">Tên thể loại <span class="text-danger">*</span></label>
    <input type="text" id="name" name="name" value="{{ old('name', $genre->name) }}" class="form-control @error('name') is-invalid @enderror" autofocus>
    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    <div class="form-text">Slug (đường dẫn) sẽ được tạo tự động từ tên.</div>
</div>
<div class="mb-4">
    <label for="description" class="form-label">Mô tả</label>
    <input type="text" id="description" name="description" value="{{ old('description', $genre->description) }}" class="form-control @error('description') is-invalid @enderror">
    @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>
<div class="d-flex gap-2">
    <button class="btn btn-primary px-4"><i class="bi bi-check-lg me-1"></i>Lưu</button>
    <a href="{{ route('admin.genres.index') }}" class="btn btn-light">Hủy</a>
</div>
