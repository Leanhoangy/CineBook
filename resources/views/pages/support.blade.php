@extends('layouts.app')

@section('title', 'Hỗ trợ khách hàng')

@section('content')
    @php
        $contact = config('cinema.contact');
        $faqs = [
            'Đặt vé' => [
                ['Làm sao để đặt vé?', 'Chọn phim → chọn ngày và suất chiếu → chọn ghế trên sơ đồ phòng chiếu → nhập mã giảm giá (nếu có) → xác nhận. Bạn cần đăng nhập để đặt vé.'],
                ['Một lần đặt được bao nhiêu ghế?', 'Tối đa '.config('cinema.max_seats_per_booking').' ghế cho mỗi đơn. Hệ thống giữ ghế ngay khi bạn xác nhận nên hai người không thể đặt trùng một ghế.'],
                ['Tôi nhận vé ở đâu?', 'Vé điện tử (mã vé + mã QR) được gửi qua email ngay sau khi đặt và luôn có trong mục "Vé của tôi". Khi đến rạp, đưa mã QR hoặc mã vé cho nhân viên tại quầy.'],
                ['Có đặt vé trước cho phim sắp chiếu được không?', 'Được. Phim sắp chiếu được mở bán vé từ ngày khởi chiếu khi đã có lịch chiếu trong 7 ngày tới, có nhãn "Đặt vé trước" ở trang lịch chiếu.'],
            ],
            'Thanh toán & hủy vé' => [
                ['Thanh toán như thế nào?', 'Sau khi đặt vé, bạn có thể thanh toán online ngay trên trang vé qua cổng VNPay (thẻ ATM nội địa, Visa/Master hoặc quét QR bằng ứng dụng ngân hàng), hoặc trả tại quầy khi đến nhận vé. Số tiền cần trả là "Tổng cộng" trên vé điện tử (đã trừ mã giảm giá).'],
                ['Đặt vé rồi nhưng chưa thanh toán thì sao?', 'Vé chưa thanh toán được giữ chỗ đến hạn thanh toán: trước giờ chiếu '.config('cinema.unpaid_release_minutes').' phút (đặt sát giờ thì có ít nhất '.config('cinema.payment_grace_minutes').' phút). Quá hạn, vé tự hủy để mở bán lại ghế và bạn nhận được email thông báo. Hạn thanh toán ghi rõ trên vé điện tử.'],
                ['Tôi có thể hủy vé không?', 'Vé chưa thanh toán: bạn tự hủy được trong mục "Vé của tôi" trước giờ chiếu ít nhất '.config('cinema.cancel_before_minutes').' phút; ghế được mở lại cho người khác đặt và lượt dùng mã giảm giá (nếu có) được hoàn lại. Vé đã thanh toán không hỗ trợ hủy hay hoàn tiền khi bạn đổi ý (giống quy định chung của các rạp).'],
                ['Khi nào tôi được hoàn tiền?', 'Khi lỗi từ phía rạp: rạp hủy suất chiếu do sự cố, hoặc bạn thanh toán xong đúng lúc vé vừa tự hủy vì quá hạn. Rạp hoàn lại toàn bộ số tiền và gửi email báo cho bạn: vé trả qua VNPay được hoàn về đúng thẻ/tài khoản đã dùng (thường trong vài ngày làm việc tùy ngân hàng), vé trả tại quầy nhận lại tiền mặt tại quầy. Trạng thái hoàn tiền hiện ngay trên vé điện tử.'],
                ['Dùng mã giảm giá thế nào?', 'Nhập mã ở bước chọn ghế, hệ thống tự kiểm tra và tính số tiền giảm. Mỗi đơn dùng 1 mã, mỗi khách chỉ dùng mỗi mã 1 lần. Xem các mã đang áp dụng ở trang Ưu đãi.'],
            ],
            'Tại rạp' => [
                ['Phân loại độ tuổi P, T13, T16, T18 là gì?', 'P: phim dành cho mọi lứa tuổi. T13, T16, T18: phim dành cho khán giả từ đủ 13, 16, 18 tuổi trở lên. Nhân viên có thể yêu cầu xuất trình giấy tờ tùy thân có ảnh khi vào phòng chiếu.'],
                ['Tôi nên đến rạp trước bao lâu?', 'Nên có mặt trước giờ chiếu khoảng 15 phút để nhận vé và ổn định chỗ ngồi. Suất chiếu bắt đầu đúng giờ ghi trên vé.'],
                ['Ghế VIP khác gì ghế thường?', 'Ghế VIP nằm ở các hàng cuối rạp, góc nhìn bao quát màn hình, phụ thu '.number_format(config('cinema.vip_surcharge'), 0, ',', '.').' ₫/ghế.'],
            ],
            'Tài khoản' => [
                ['Tôi quên mật khẩu?', 'Bấm "Quên mật khẩu?" ở trang đăng nhập và nhập email. Link đặt lại mật khẩu được gửi vào hộp thư, có hiệu lực trong 60 phút.'],
                ['Đổi thông tin cá nhân ở đâu?', 'Vào mục "Tài khoản" ở góc trên bên phải để đổi họ tên, số điện thoại, ảnh đại diện hoặc mật khẩu.'],
            ],
        ];
    @endphp

    <div class="container py-4 py-lg-5">
        <h1 class="cb-section-title mb-1">Hỗ trợ khách hàng</h1>
        <p class="text-muted mb-4">Giải đáp các câu hỏi thường gặp khi đặt vé và xem phim tại CineBook.</p>

        <div class="row g-4">
            <div class="col-lg-8">
                @foreach ($faqs as $group => $items)
                    <h2 class="h5 fw-bold mt-2 mb-3">{{ $group }}</h2>
                    <div class="accordion cb-faq mb-4" id="faq-{{ $loop->index }}">
                        @foreach ($items as [$question, $answer])
                            @php $id = "faq-{$loop->parent->index}-{$loop->index}"; @endphp
                            <div class="accordion-item">
                                <h3 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $id }}" aria-expanded="false" aria-controls="{{ $id }}">
                                        {{ $question }}
                                    </button>
                                </h3>
                                <div id="{{ $id }}" class="accordion-collapse collapse" data-bs-parent="#faq-{{ $loop->parent->index }}">
                                    <div class="accordion-body text-muted">{{ $answer }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>

            <div class="col-lg-4">
                <div class="cb-card p-4 cb-summary">
                    <h2 class="h5 fw-bold mb-3">Liên hệ</h2>
                    <ul class="list-unstyled cb-contact mb-0">
                        <li><i class="bi bi-geo-alt"></i><span>{{ $contact['address'] }}</span></li>
                        <li><i class="bi bi-telephone"></i><span>Hotline <strong class="text-body">{{ $contact['hotline'] }}</strong></span></li>
                        <li><i class="bi bi-envelope"></i><span>{{ $contact['email'] }}</span></li>
                        <li><i class="bi bi-clock"></i><span>{{ $contact['hours'] }}</span></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection
