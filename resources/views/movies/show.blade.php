@extends('layouts.app')

@section('title', $movie->title)

@section('content')
    <section class="cb-detail-hero py-4 py-lg-5" style="--h: {{ $movie->poster_hue }}">
        <div class="container">
            <nav aria-label="breadcrumb" class="mb-3">
                <ol class="breadcrumb small mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Trang chủ</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('movies.index') }}">Phim</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $movie->title }}</li>
                </ol>
            </nav>
            <div class="row g-4 g-lg-5">
                <div class="col-sm-5 col-md-4 col-lg-3">
                    <x-poster :movie="$movie" />
                </div>
                <div class="col-sm-7 col-md-8 col-lg-9">
                    <x-status-badge :status="$movie->status" class="mb-2" />
                    <h1 class="mb-3">{{ $movie->title }}</h1>
                    <div class="d-flex flex-wrap gap-3 text-muted mb-3">
                        <span><i class="bi bi-clock me-1"></i>{{ $movie->duration_label }}</span>
                        <span><i class="bi bi-calendar-event me-1"></i>{{ $movie->release_date->format('d/m/Y') }}</span>
                        <span><span class="cb-age position-static {{ $movie->age_rating }}">{{ $movie->age_rating }}</span> {{ \App\Models\Movie::AGE_RATINGS[$movie->age_rating] }}</span>
                    </div>
                    <div class="d-flex flex-wrap gap-2 mb-4">
                        @foreach ($movie->genres as $genre)
                            <a href="{{ route('movies.index', ['the-loai' => $genre->slug]) }}" class="cb-chip">{{ $genre->name }}</a>
                        @endforeach
                    </div>
                    <p class="mb-4" style="max-width: 760px; line-height: 1.7">{{ $movie->description }}</p>
                    <dl class="row cb-info-list mb-4" style="max-width: 760px">
                        @if ($movie->director)
                            <dt class="col-sm-3">Đạo diễn</dt><dd class="col-sm-9">{{ $movie->director }}</dd>
                        @endif
                        @if ($movie->cast)
                            <dt class="col-sm-3">Diễn viên</dt><dd class="col-sm-9">{{ $movie->cast }}</dd>
                        @endif
                    </dl>
                    <div class="d-flex flex-wrap gap-2">
                        @if ($showtimesByDate->isNotEmpty())
                            <a href="#lich-chieu" class="btn btn-primary btn-lg rounded-pill px-4"><i class="bi bi-ticket-perforated me-2"></i>Chọn suất chiếu</a>
                        @endif
                        @if ($movie->trailer_embed_url)
                            <button type="button" class="btn btn-outline-light btn-lg rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#trailerModal"><i class="bi bi-play-circle me-2"></i>Xem trailer</button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="container py-5">
        <section id="lich-chieu" class="mb-5">
            <h2 class="cb-section-title mb-4">Lịch chiếu</h2>

            @if ($showtimesByDate->isEmpty())
                <div class="cb-card cb-empty">
                    <i class="bi bi-calendar-x"></i>
                    @if ($movie->status === 'coming_soon')
                        Phim sẽ khởi chiếu từ ngày {{ $movie->release_date->format('d/m/Y') }}. Hãy quay lại sau nhé!
                    @else
                        Hiện chưa có suất chiếu nào trong 7 ngày tới.
                    @endif
                </div>
            @else
                <div class="cb-card p-3 p-md-4">
                    <div class="cb-date-tabs mb-4" role="tablist">
                        @foreach ($showtimesByDate as $date => $items)
                            @php $d = \Illuminate\Support\Carbon::parse($date); @endphp
                            <button class="cb-date-tab {{ $loop->first ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#day-{{ $date }}" type="button" role="tab">
                                <small>{{ $d->isToday() ? 'Hôm nay' : $d->translatedFormat('l') }}</small>
                                <strong>{{ $d->format('d/m') }}</strong>
                            </button>
                        @endforeach
                    </div>

                    <div class="tab-content">
                        @foreach ($showtimesByDate as $date => $items)
                            <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="day-{{ $date }}" role="tabpanel">
                                <div class="d-flex flex-wrap gap-2">
                                    @foreach ($items as $showtime)
                                        <a href="{{ route('bookings.create', $showtime) }}" class="cb-time-btn">
                                            <strong>{{ $showtime->start_time->format('H:i') }}</strong>
                                            <small>{{ $showtime->room->name }} · @money($showtime->price)</small>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @auth
                        @if (auth()->user()->isAdmin())
                            <p class="small text-warning mt-3 mb-0"><i class="bi bi-info-circle me-1"></i>Bạn đang đăng nhập bằng tài khoản quản trị. Hãy dùng tài khoản khách hàng để đặt vé.</p>
                        @endif
                    @else
                        <p class="small text-muted mt-3 mb-0"><i class="bi bi-info-circle me-1"></i>Bạn cần <a href="{{ route('login') }}">đăng nhập</a> để đặt vé.</p>
                    @endauth
                </div>
            @endif
        </section>

        <section id="danh-gia" class="mb-5">
            <h2 class="cb-section-title mb-4">Đánh giá từ khán giả</h2>
            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="cb-card p-4 text-center">
                        @if ($ratingStats['count'])
                            <div class="cb-rating-big">{{ number_format($ratingStats['average'], 1, ',') }}<small>/5</small></div>
                            <x-stars :rating="$ratingStats['average']" class="fs-5 mb-1" />
                            <div class="small text-muted mb-3">{{ $ratingStats['count'] }} lượt đánh giá</div>
                            @foreach (range(5, 1) as $star)
                                @php $percent = round(($ratingStats['distribution'][$star] ?? 0) * 100 / $ratingStats['count']); @endphp
                                <div class="d-flex align-items-center gap-2 small">
                                    <span class="text-nowrap" style="width: 2.2rem">{{ $star }} <i class="bi bi-star-fill cb-star"></i></span>
                                    <div class="progress flex-grow-1" style="height: 6px" role="progressbar" aria-label="{{ $star }} sao" aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100">
                                        <div class="progress-bar bg-warning" style="width: {{ $percent }}%"></div>
                                    </div>
                                    <span class="text-muted text-end" style="width: 2.4rem">{{ $percent }}%</span>
                                </div>
                            @endforeach
                        @else
                            <i class="bi bi-chat-square-heart fs-1 text-muted d-block mb-2"></i>
                            <p class="text-muted mb-0">Chưa có đánh giá nào cho phim này.</p>
                        @endif
                    </div>
                </div>

                <div class="col-lg-8">
                    @if ($canReview)
                        <div class="cb-card p-3 p-md-4 mb-4">
                            <h3 class="h6 fw-bold mb-3">{{ $myReview ? 'Đánh giá của bạn' : 'Bạn thấy phim thế nào?' }}</h3>
                            @if ($myReview?->is_hidden)
                                <div class="alert alert-warning small py-2">Đánh giá của bạn đã bị quản trị viên ẩn do vi phạm quy định bình luận.</div>
                            @endif
                            <form method="POST" action="{{ route('reviews.store', $movie) }}" novalidate>
                                @csrf
                                <div class="cb-star-input mb-2" role="radiogroup" aria-label="Chọn số sao">
                                    @foreach (range(5, 1) as $star)
                                        <input type="radio" name="rating" id="star{{ $star }}" value="{{ $star }}" @checked(old('rating', $myReview?->rating) == $star)>
                                        <label for="star{{ $star }}" title="{{ $star }} sao"><i class="bi bi-star-fill"></i></label>
                                    @endforeach
                                </div>
                                @error('rating') <div class="text-danger small mb-2">{{ $message }}</div> @enderror
                                <textarea name="content" rows="3" maxlength="1000" class="form-control mb-3 @error('content') is-invalid @enderror" placeholder="Chia sẻ cảm nhận của bạn về phim (không bắt buộc)">{{ old('content', $myReview?->content) }}</textarea>
                                @error('content') <div class="invalid-feedback d-block mb-2">{{ $message }}</div> @enderror
                                <div class="d-flex gap-2">
                                    <button class="btn btn-primary rounded-pill px-4">{{ $myReview ? 'Cập nhật đánh giá' : 'Gửi đánh giá' }}</button>
                                </div>
                            </form>
                            @if ($myReview)
                                <form method="POST" action="{{ route('reviews.destroy', $myReview) }}" class="mt-2" data-confirm="Xóa đánh giá của bạn?">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-link btn-sm text-danger p-0">Xóa đánh giá</button>
                                </form>
                            @endif
                        </div>
                    @else
                        <p class="small text-muted mb-4">
                            <i class="bi bi-info-circle me-1"></i>
                            @guest
                                <a href="{{ route('login') }}">Đăng nhập</a> và xem phim tại CineBook để gửi đánh giá.
                            @else
                                Chỉ khách đã xem phim tại CineBook (có vé của suất chiếu đã diễn ra) mới có thể đánh giá.
                            @endguest
                        </p>
                    @endif

                    @forelse ($reviews as $review)
                        <div class="cb-review">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                @if ($review->user->avatar_url)
                                    <img src="{{ $review->user->avatar_url }}" alt="" class="cb-avatar">
                                @else
                                    <span class="cb-avatar">{{ $review->user->initials }}</span>
                                @endif
                                <div>
                                    <div class="fw-semibold small">{{ $review->user->name }} <span class="badge text-bg-success fw-normal ms-1"><i class="bi bi-patch-check me-1"></i>Đã xem phim</span></div>
                                    <x-stars :rating="$review->rating" class="small" />
                                </div>
                                <small class="text-muted ms-auto">{{ $review->created_at->diffForHumans() }}</small>
                            </div>
                            @if ($review->content)
                                <p class="mb-0 mt-2">{{ $review->content }}</p>
                            @endif
                        </div>
                    @empty
                        @if ($ratingStats['count'] === 0 && ! $canReview)
                            <div class="cb-card cb-empty"><i class="bi bi-chat-dots"></i>Hãy là người đầu tiên đánh giá phim này sau khi xem!</div>
                        @endif
                    @endforelse

                    @if ($reviews->hasPages())
                        <div class="mt-3">{{ $reviews->links() }}</div>
                    @endif
                </div>
            </div>
        </section>

        @if ($movie->trailer_embed_url)
            <div class="modal fade" id="trailerModal" tabindex="-1" aria-label="Trailer" aria-hidden="true">
                <div class="modal-dialog modal-xl modal-dialog-centered">
                    <div class="modal-content bg-black border-0">
                        <div class="ratio ratio-16x9">
                            <iframe data-src="{{ $movie->trailer_embed_url }}?autoplay=1" title="Trailer {{ $movie->title }}" allow="autoplay; encrypted-media" allowfullscreen></iframe>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        @if ($related->isNotEmpty())
            <section>
                <h2 class="cb-section-title mb-4">Phim cùng thể loại</h2>
                <div class="row row-cols-2 row-cols-md-4 g-3 g-lg-4">
                    @foreach ($related as $item)
                        <div class="col"><x-movie-card :movie="$item" /></div>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
@endsection

@push('scripts')
    <script>
        // Chỉ tải video trailer khi mở modal, dừng video khi đóng
        const trailer = document.getElementById('trailerModal');
        if (trailer) {
            const frame = trailer.querySelector('iframe');
            trailer.addEventListener('show.bs.modal', () => frame.src = frame.dataset.src);
            trailer.addEventListener('hidden.bs.modal', () => frame.src = '');
        }
    </script>
@endpush
