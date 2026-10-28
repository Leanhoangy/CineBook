<?php

return [
    // Phụ thu cho mỗi ghế VIP (VNĐ)
    'vip_surcharge' => 20000,

    // Thời gian dọn phòng giữa hai suất chiếu (phút)
    'cleaning_minutes' => 15,

    // Số ghế tối đa cho một lần đặt
    'max_seats_per_booking' => 8,

    // Khách được tự hủy vé trước giờ chiếu tối thiểu bao nhiêu phút
    'cancel_before_minutes' => 60,

    // Mở soát vé cho vào rạp trước giờ chiếu bao nhiêu phút (đến hết suất chiếu)
    'checkin_before_minutes' => 60,

    // Vé chưa thanh toán phải trả tiền (online hoặc tại quầy) trước giờ chiếu bao nhiêu phút, quá hạn sẽ tự hủy để mở bán lại ghế.
    // Khách đặt sát giờ vẫn có tối thiểu payment_grace_minutes phút để thanh toán.
    'unpaid_release_minutes' => 30,
    'payment_grace_minutes' => 15,

    // Bảng giá vé ghế thường theo khung giờ (VNĐ) – dùng khi tự xếp lịch chiếu và hiển thị ở trang Ưu đãi
    'pricing' => [
        'morning' => 60000,          // suất bắt đầu trước 12h
        'afternoon' => 75000,        // 12h – trước 17h
        'evening' => 85000,          // từ 17h
        'weekend_surcharge' => 10000, // thứ Bảy, Chủ nhật
        'tuesday' => 55000,          // "Thứ Ba vui vẻ": đồng giá cả ngày
    ],

    // Thông tin liên hệ hiển thị ở chân trang và trang Hỗ trợ (rạp giả lập cho đồ án)
    'contact' => [
        'address' => 'Tầng 5, TTTM CineBook Plaza, 123 Nguyễn Văn Phim, Quận 1, TP. Hồ Chí Minh',
        'hotline' => '1900 0000',
        'email' => 'hotro@cinebook.test',
        'hours' => '08:30 – 23:30 hằng ngày',
    ],
];
