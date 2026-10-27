@extends('layouts.app')

@section('title', 'Ưu đãi & giá vé')

@section('content')
    @php
        $money = fn (int $n) => number_format($n, 0, ',', '.').' ₫';
        $weekend = fn (int $n) => $money($n + $pricing['weekend_surcharge']);
        $vip = (int) config('cinema.vip_surcharge');
    @endphp

    <div class="container py-4 py-lg-5">
        <h1 class="cb-section-title mb-1">Ưu đãi & giá vé</h1>
        <p class="text-muted mb-4">Các chương trình khuyến mãi, mã giảm giá và bảng giá vé đang áp dụng tại CineBook.</p>

        {{-- Chương trình giá vé --}}
        <section class="mb-5">
            <div class="row g-3">
                @foreach ($programs as $program)
                    <div class="col-md-4"><x-promo-card :program="$program" /></div>
                @endforeach
            </div>
        </section>

        {{-- Mã giảm giá --}}
        <section class="mb-5" id="ma-giam-gia">
            <h2 class="cb-section-title h4 mb-1">Mã giảm giá</h2>
            <p class="text-muted small mb-3">Sao chép mã và nhập ở bước chọn ghế, hệ thống sẽ tự tính số tiền được giảm.</p>

            @if ($vouchers->isEmpty())
                <div class="cb-card cb-empty"><i class="bi bi-ticket-perforated"></i>Hiện chưa có mã giảm giá nào. Hãy quay lại sau nhé!</div>
            @else
                <div class="row g-3">
                    @foreach ($vouchers as $voucher)
                        <div class="col-lg-6"><x-voucher-ticket :voucher="$voucher" /></div>
                    @endforeach
                </div>
            @endif
        </section>

        {{-- Bảng giá vé --}}
        <section class="mb-5" id="bang-gia">
            <h2 class="cb-section-title h4 mb-3">Bảng giá vé</h2>
            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="cb-card p-0 overflow-hidden">
                        <div class="table-responsive">
                            <table class="table cb-price-table mb-0">
                                <thead>
                                    <tr>
                                        <th>Khung giờ</th>
                                        <th>Thứ Hai, Thứ Tư – Thứ Sáu</th>
                                        <th class="text-danger">Thứ Ba vui vẻ</th>
                                        <th>Thứ Bảy, Chủ nhật</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ([['Trước 12:00', 'morning'], ['12:00 – 16:59', 'afternoon'], ['Từ 17:00', 'evening']] as [$label, $slot])
                                        <tr>
                                            <th>{{ $label }}</th>
                                            <td>{{ $money($pricing[$slot]) }}</td>
                                            @if ($loop->first)
                                                <td rowspan="3" class="align-middle text-danger fw-bold fs-5">{{ $money($pricing['tuesday']) }}<br><small class="fw-normal text-muted fs-6">cả ngày</small></td>
                                            @endif
                                            <td>{{ $weekend($pricing[$slot]) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <ul class="small text-muted mt-3 mb-0 ps-3">
                        <li>Giá trên áp dụng cho <strong>ghế thường</strong>; ghế VIP phụ thu {{ $money($vip) }}/ghế.</li>
                        <li>Mã giảm giá được trừ trên tổng tiền vé; mỗi đơn dùng 1 mã.</li>
                        <li>Giá có thể thay đổi vào các ngày lễ, Tết hoặc suất chiếu đặc biệt; giá chính xác hiển thị ở bước chọn ghế.</li>
                    </ul>
                </div>

                {{-- Loại ghế & phòng chiếu --}}
                <div class="col-lg-4">
                    <div class="cb-card p-4 mb-3">
                        <div class="d-flex align-items-center gap-3 mb-2">
                            <span class="cb-seat cb-seat-icon"></span>
                            <h3 class="h6 fw-bold mb-0">Ghế thường</h3>
                        </div>
                        <p class="text-muted small mb-0">Các hàng phía trước và giữa rạp, giá theo bảng giá bên cạnh.</p>
                    </div>
                    <div class="cb-card p-4 mb-3">
                        <div class="d-flex align-items-center gap-3 mb-2">
                            <span class="cb-seat cb-seat-icon vip"></span>
                            <h3 class="h6 fw-bold mb-0">Ghế VIP <span class="text-warning">+{{ $money($vip) }}</span></h3>
                        </div>
                        <p class="text-muted small mb-0">Các hàng cuối rạp – góc nhìn bao quát màn hình, được nhiều khán giả chọn nhất.</p>
                    </div>
                    @if ($rooms->isNotEmpty())
                        <div class="cb-card p-4">
                            <h3 class="h6 fw-bold mb-2"><i class="bi bi-display me-2 text-danger"></i>{{ $rooms->count() }} phòng chiếu</h3>
                            <p class="text-muted small mb-0">
                                Lớn nhất là <strong class="text-body">{{ $rooms->first()->name }}</strong> với {{ $rooms->first()->capacity }} ghế
                                ({{ $rooms->first()->total_rows }} hàng × {{ $rooms->first()->seats_per_row }} ghế).
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        </section>
    </div>
@endsection
