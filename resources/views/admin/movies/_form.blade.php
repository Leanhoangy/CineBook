@include('partials.form-errors')

@php $selectedGenres = old('genres', $movie->exists ? $movie->genres->pluck('id')->all() : []); @endphp

<div class="row g-4">
    <div class="col-lg-8">
        <div class="ad-card p-4">
            <div class="row g-3">
                <div class="col-12">
                    <label for="title" class="form-label">Tên phim <span class="text-danger">*</span></label>
                    <input type="text" id="title" name="title" value="{{ old('title', $movie->title) }}" class="form-control @error('title') is-invalid @enderror">
                    @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-12">
                    <label for="description" class="form-label">Nội dung phim <span class="text-danger">*</span></label>
                    <textarea id="description" name="description" rows="5" class="form-control @error('description') is-invalid @enderror">{{ old('description', $movie->description) }}</textarea>
                    @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label for="director" class="form-label">Đạo diễn</label>
                    <input type="text" id="director" name="director" value="{{ old('director', $movie->director) }}" class="form-control @error('director') is-invalid @enderror">
                    @error('director') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label for="cast" class="form-label">Diễn viên</label>
                    <input type="text" id="cast" name="cast" value="{{ old('cast', $movie->cast) }}" class="form-control @error('cast') is-invalid @enderror" placeholder="Cách nhau bởi dấu phẩy">
                    @error('cast') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label for="duration" class="form-label">Thời lượng (phút) <span class="text-danger">*</span></label>
                    <input type="number" id="duration" name="duration" min="30" max="300" value="{{ old('duration', $movie->duration) }}" class="form-control @error('duration') is-invalid @enderror">
                    @error('duration') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label for="release_date" class="form-label">Ngày khởi chiếu <span class="text-danger">*</span></label>
                    <input type="date" id="release_date" name="release_date" value="{{ old('release_date', $movie->release_date?->toDateString()) }}" class="form-control @error('release_date') is-invalid @enderror">
                    @error('release_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label for="age_rating" class="form-label">Phân loại <span class="text-danger">*</span></label>
                    <select id="age_rating" name="age_rating" class="form-select @error('age_rating') is-invalid @enderror">
                        @foreach (\App\Models\Movie::AGE_RATINGS as $value => $label)
                            <option value="{{ $value }}" @selected(old('age_rating', $movie->age_rating) === $value)>{{ $value }} – {{ $label }}</option>
                        @endforeach
                    </select>
                    @error('age_rating') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-12">
                    <label for="trailer_url" class="form-label">Link trailer YouTube</label>
                    <input type="url" id="trailer_url" name="trailer_url" value="{{ old('trailer_url', $movie->trailer_url) }}" class="form-control @error('trailer_url') is-invalid @enderror" placeholder="https://www.youtube.com/watch?v=...">
                    @error('trailer_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-12">
                    <label class="form-label">Thể loại <span class="text-danger">*</span></label>
                    <div class="d-flex flex-wrap gap-2 p-2 border rounded @error('genres') border-danger @enderror">
                        @foreach ($genres as $genre)
                            <input type="checkbox" class="btn-check" name="genres[]" id="genre-{{ $genre->id }}" value="{{ $genre->id }}" autocomplete="off" @checked(in_array($genre->id, $selectedGenres))>
                            <label class="btn btn-sm btn-outline-secondary rounded-pill" for="genre-{{ $genre->id }}">{{ $genre->name }}</label>
                        @endforeach
                    </div>
                    @error('genres') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="ad-card p-4 mb-4">
            <label for="status" class="form-label">Trạng thái <span class="text-danger">*</span></label>
            <select id="status" name="status" class="form-select @error('status') is-invalid @enderror">
                @foreach (\App\Models\Movie::STATUSES as $value => $label)
                    <option value="{{ $value }}" @selected(old('status', $movie->status) === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="ad-card p-4 mb-4">
            <label for="poster" class="form-label d-block">Poster</label>
            <div class="mb-3">
                <img id="posterPreview" src="{{ $movie->poster_url }}" alt="" class="ad-poster-preview {{ $movie->poster_url ? '' : 'd-none' }}">
                @unless ($movie->poster_url)
                    <div id="posterEmpty" class="ad-poster-preview d-flex align-items-center justify-content-center text-muted"><i class="bi bi-image fs-2"></i></div>
                @endunless
            </div>
            <input type="file" id="poster" name="poster" accept="image/*" class="form-control @error('poster') is-invalid @enderror" data-preview="#posterPreview" data-hide="#posterEmpty">
            <div class="form-text">JPG, PNG, WEBP – tối đa 3MB. Tỉ lệ 2:3.</div>
            @error('poster') <div class="invalid-feedback">{{ $message }}</div> @enderror
            @if ($movie->poster)
                <div class="form-check mt-2">
                    <input class="form-check-input" type="checkbox" name="remove_poster" value="1" id="remove_poster">
                    <label class="form-check-label small" for="remove_poster">Xóa poster hiện tại</label>
                </div>
            @endif
        </div>

        <div class="d-grid gap-2">
            <button class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Lưu phim</button>
            <a href="{{ $movie->exists ? route('admin.movies.show', $movie) : route('admin.movies.index') }}" class="btn btn-light">Hủy</a>
        </div>
    </div>
</div>
