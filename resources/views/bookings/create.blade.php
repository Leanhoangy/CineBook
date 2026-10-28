@extends('layouts.app')

@section('title', 'Chọn ghế – '.$showtime->movie->title)

@section('content')
    @php
        $movie = $showtime->movie;
        $room = $showtime->room;
        $standardPrice = $showtime->seatPrice('standard');
        $vipPrice = $showtime->seatPrice('vip');
        $half = intdiv($room->seats_per_row, 2);
    @endphp

    <div class="container py-4 py-lg-5">
        <a href="{{ route('movies.show', $movie) }}" class="small text-muted text-decoration-none"><i class="bi bi-arrow-left me-1"></i>Quay lại {{ $movie->title }}</a>

        <div class="d-flex flex-wrap align-items-center gap-3 mt-2 mb-4">
            <h1 class="h3 fw-bold mb-0">Chọn ghế</h1>
            <span class="text-muted">{{ $room->name }} · {{ $showtime->start_time->translatedFormat('l, d/m/Y') }} · {{ $showtime->start_time->format('H:i') }}</span>
        </div>

        @if ($otherShowtimes->count() > 1)
            <div class="d-flex flex-wrap gap-2 mb-4">
                @foreach ($otherShowtimes as $other)
                    <a href="{{ route('bookings.create', $other) }}" class="cb-time-btn {{ $other->is($showtime) ? 'current' : '' }}">
                        <strong>{{ $other->start_time->format('H:i') }}</strong>
                        <small>{{ $other->room->name }}</small>
                    </a>
                @endforeach
            </div>
        @endif

        @include('partials.form-errors')

        <form method="POST" action="{{ route('bookings.store', $showtime) }}" id="bookingForm">
            @csrf
            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="cb-card p-3 p-md-4">
                        <div class="cb-screen">MÀN HÌNH</div>
                        <p class="d-md-none text-center small text-muted mb-3"><i class="bi bi-arrows-expand-vertical me-1"></i>Vuốt ngang để xem toàn bộ sơ đồ ghế</p>

                        <div class="cb-seatmap" id="seatPicker"
                             data-max="{{ config('cinema.max_seats_per_booking') }}"
                             data-status-url="{{ route('showtimes.seats', $showtime) }}"
                             data-old='@json(old('seats', []))'>
                            <div class="cb-seat-rows">
                                @foreach ($room->seatMap() as $row => $seats)
                                    @php $vip = $room->isVipRow($row); @endphp
                                    <div class="cb-seat-row">
                                        <span class="cb-row-label">{{ $row }}</span>
                                        @foreach ($seats as $code)
                                            @php $booked = in_array($code, $bookedSeats, true); @endphp
                                            <button type="button"
                                                    class="cb-seat {{ $vip ? 'vip' : '' }} {{ $booked ? 'booked' : '' }}"
                                                    data-code="{{ $code }}"
                                                    data-price="{{ $vip ? $vipPrice : $standardPrice }}"
                                                    title="Ghế {{ $code }}{{ $vip ? ' (VIP)' : '' }}"
                                                    aria-label="Ghế {{ $code }}" aria-pressed="false"
                                                    @disabled($booked)><span>{{ substr($code, 1) }}</span></button>
                                            @if ($loop->iteration === $half)
                                                <span class="cb-seat-gap"></span>
                                            @endif
                                        @endforeach
                                        <span class="cb-row-label">{{ $row }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="cb-legend mt-4">
                            <span><span class="cb-seat"></span>Ghế thường · @money($standardPrice)</span>
                            <span><span class="cb-seat vip"></span>Ghế VIP · @money($vipPrice)</span>
                            <span><span class="cb-seat selected"></span>Đang chọn</span>
                            <span><span class="cb-seat booked"></span>Đã đặt</span>
                        </div>
                        <p class="text-center small text-muted mt-3 mb-0"><i class="bi bi-broadcast me-1 text-success"></i><span id="liveStatus">Sơ đồ ghế tự động cập nhật mỗi 10 giây</span></p>
                    </div>

                    @if ($combos->isNotEmpty())
                        <div class="cb-card p-3 p-md-4 mt-4" id="comboBox">
                            <h2 class="h5 fw-bold mb-1"><i class="bi bi-cup-straw me-2 text-danger"></i>Bắp nước</h2>
                            <p class="small text-muted mb-3">Đặt trước, nhận tại quầy khi soát vé – không phải xếp hàng mua.</p>
                            @error('combos') <div class="alert alert-danger py-2 small">{{ $message }}</div> @enderror
                            <div class="row g-3">
                                @foreach ($combos as $combo)
                                    <div class="col-sm-6">
                                        <div class="cb-combo" data-combo-id="{{ $combo->id }}" data-name="{{ $combo->name }}" data-price="{{ $combo->price }}">
                                            @if ($combo->image_url)
                                                <img src="{{ $combo->image_url }}" alt="{{ $combo->name }}" class="cb-combo-img" loading="lazy">
                                            @else
                                                <span class="cb-combo-img cb-combo-img-fallback" aria-hidden="true">🍿</span>
                                            @endif
                                            <div class="flex-grow-1 min-w-0">
                                                <div class="fw-semibold">{{ $combo->name }}</div>
                                                <div class="small text-muted">{{ $combo->description }}</div>
                                                <div class="d-flex justify-content-between align-items-center mt-2">
                                                    <strong class="text-warning">@money($combo->price)</strong>
                                                    <div class="cb-qty">
                                                        <button type="button" data-step="-1" aria-label="Bớt {{ $combo->name }}"><i class="bi bi-dash"></i></button>
                                                        <input type="number" name="combos[{{ $combo->id }}]" value="{{ old("combos.{$combo->id}", 0) }}" min="0" max="{{ \App\Models\Combo::MAX_QUANTITY }}" readonly aria-label="Số lượng {{ $combo->name }}">
                                                        <button type="button" data-step="1" aria-label="Thêm {{ $combo->name }}"><i class="bi bi-plus"></i></button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                <div class="col-lg-4">
                    <div class="cb-card p-3 p-md-4 cb-summary">
                        <div class="d-flex gap-3 mb-3">
                            <x-poster :movie="$movie" :show-age="false" style="width: 80px; flex-shrink: 0; border-radius: 10px" />
                            <div>
                                <h2 class="h6 fw-bold mb-1">{{ $movie->title }}</h2>
                                <div class="small text-muted"><span class="cb-age position-static {{ $movie->age_rating }}">{{ $movie->age_rating }}</span> {{ $movie->duration }} phút</div>
                            </div>
                        </div>
                        <ul class="list-unstyled small mb-3">
                            <li class="d-flex justify-content-between py-1"><span class="text-muted">Rạp</span><strong>{{ $room->name }}</strong></li>
                            <li class="d-flex justify-content-between py-1"><span class="text-muted">Ngày chiếu</span><strong>{{ $showtime->start_time->format('d/m/Y') }}</strong></li>
                            <li class="d-flex justify-content-between py-1"><span class="text-muted">Giờ chiếu</span><strong>{{ $showtime->start_time->format('H:i') }} – {{ $showtime->start_time->copy()->addMinutes($movie->duration)->format('H:i') }}</strong></li>
                        </ul>
                        <hr>
                        <div class="mb-2 d-flex justify-content-between"><span class="text-muted small">Ghế đã chọn (<span id="seatCount">0</span>)</span></div>
                        <div id="selectedSeats" class="cb-seat-tags mb-3"></div>
                        <div id="seatInputs"></div>

                        <div class="mb-3">
                            <label for="note" class="form-label small text-muted">Ghi chú (không bắt buộc)</label>
                            <input type="text" id="note" name="note" value="{{ old('note') }}" maxlength="255" class="form-control form-control-sm @error('note') is-invalid @enderror" placeholder="VD: Cần ghế gần lối đi">
                        </div>

                        <div class="mb-3" id="voucherBox" data-check-url="{{ route('bookings.voucher', $showtime) }}">
                            <label for="voucherInput" class="form-label small text-muted">Mã giảm giá</label>
                            <div class="input-group input-group-sm">
                                <input type="text" id="voucherInput" name="voucher_code" value="{{ old('voucher_code') }}" maxlength="30" class="form-control text-uppercase @error('voucher_code') is-invalid @enderror" placeholder="VD: CINEBOOK10" autocomplete="off">
                                <button type="button" class="btn btn-outline-light" id="voucherApply">Áp dụng</button>
                            </div>
                            <div id="voucherMessage" class="small mt-1 {{ $errors->has('voucher_code') ? 'text-danger' : '' }}">@error('voucher_code'){{ $message }}@enderror</div>

                            @if ($vouchers->isNotEmpty())
                                <div class="small text-muted mt-3 mb-2">Mã dành cho bạn ({{ $vouchers->count() }}) · bấm để áp dụng</div>
                                <div class="cb-voucher-options" id="voucherOptions">
                                    @foreach ($vouchers as $v)
                                        <button type="button" class="cb-voucher-option" data-voucher='@json($v)'>
                                            <span class="d-flex justify-content-between align-items-center gap-2">
                                                <code class="cb-voucher-code">{{ $v['code'] }}</code>
                                                <span class="cb-voucher-hint"></span>
                                            </span>
                                            <span class="d-block text-muted mt-1">
                                                {{ $v['description'] ?: $v['label'] }}
                                                @if ($v['remaining'] !== null && $v['remaining'] <= 10)
                                                    · <span class="text-warning">còn {{ $v['remaining'] }} lượt</span>
                                                @endif
                                            </span>
                                        </button>
                                    @endforeach
                                </div>
                                <a href="{{ route('promotions') }}#ma-giam-gia" target="_blank" class="small d-inline-block mt-2">Xem điều kiện các mã <i class="bi bi-box-arrow-up-right"></i></a>
                            @endif
                        </div>

                        <div class="d-flex justify-content-between small text-muted mb-1 d-none" id="subtotalRow">
                            <span>Tiền vé</span><span id="subtotalPrice">0 ₫</span>
                        </div>
                        <div class="d-flex justify-content-between small text-success mb-1 d-none" id="discountRow">
                            <span>Giảm giá <span id="discountCode" class="fw-semibold"></span></span><span id="discountPrice">-0 ₫</span>
                        </div>
                        <div class="small text-muted mb-2 d-none" id="comboRow">
                            <div class="d-flex justify-content-between"><span>Bắp nước</span><span id="comboPrice">0 ₫</span></div>
                            <div id="comboList" class="cb-combo-list"></div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="text-muted">Tổng cộng</span>
                            <span class="total" id="totalPrice">0 ₫</span>
                        </div>
                        <button type="submit" id="submitBooking" class="btn btn-primary w-100 py-2" disabled>
                            <i class="bi bi-check2-circle me-1"></i>Xác nhận đặt vé
                        </button>
                        <p class="small text-muted mt-3 mb-0">
                            Tối đa {{ config('cinema.max_seats_per_booking') }} ghế/lần. Vé cần thanh toán (online hoặc tại quầy) trước giờ chiếu {{ config('cinema.unpaid_release_minutes') }} phút, quá hạn sẽ tự hủy.
                            Có thể hủy vé chưa thanh toán trước giờ chiếu {{ config('cinema.cancel_before_minutes') }} phút.
                        </p>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/seat-picker.js') }}?v={{ filemtime(public_path('js/seat-picker.js')) }}"></script>
@endpush
