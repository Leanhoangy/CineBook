<?php

/**
 * Bình luận mẫu cho ReviewSeeder, chia theo nhóm thể loại và số sao (2 = dùng chung cho 1–2 sao)
 * để nội dung khớp với phim: phim hoạt hình nói về hình vẽ/lồng tiếng, concert nói về sân khấu,
 * chỉ phim người đóng mới nhắc tới diễn xuất.
 *
 * "groups": nhóm => các thể loại thuộc nhóm, xét theo thứ tự từ trên xuống (phim hoạt hình hài thuộc nhóm hoạt hình).
 * "general": câu dùng được cho mọi phim, được trộn thêm vào từng nhóm.
 */
return [
    'groups' => [
        'concert' => ['Âm nhạc'],
        'documentary' => ['Tài liệu'],
        'animation' => ['Hoạt hình'],
        'horror' => ['Kinh dị'],
        'comedy' => ['Hài'],
        'action' => ['Hành động', 'Khoa học viễn tưởng', 'Phiêu lưu', 'Chiến tranh'],
        'thriller' => ['Hồi hộp', 'Bí ẩn', 'Tội phạm'],
        'romance' => ['Tình cảm'],
        'drama' => ['Tâm lý', 'Gia đình', 'Lịch sử', 'Giả tưởng'],
    ],

    'comments' => [
        'concert' => [
            5 => [
                'Âm thanh trong rạp sống động như đang đứng ở concert thật, nổi da gà mấy lần.',
                'Cả rạp hát theo và cầm lightstick cổ vũ, không khí cực vui!',
                'Góc quay sân khấu đẹp, thấy rõ biểu cảm của từng thành viên.',
                'Fan nhất định phải đi xem, phần hậu trường đáng yêu lắm.',
            ],
            4 => [
                'Setlist hay, âm thanh tốt, chỉ tiếc là hơi ngắn, xem chưa đã.',
                'Màn hình lớn xem sân khấu rất đã mắt, đáng tiền vé với fan.',
                'Không khí vui, nhiều đoạn hậu trường dễ thương.',
            ],
            3 => [
                'Hay với fan, còn người không theo dõi nhóm thì hơi khó vào.',
                'Phần sân khấu ổn nhưng cắt ghép hơi vụn, mong có nhiều bài trọn vẹn hơn.',
                'Âm thanh phòng chiếu chưa đủ lớn để cảm nhận như concert thật.',
            ],
            2 => [
                'Hầu như là cảnh đã có trên mạng, không có nhiều nội dung mới.',
                'Đi theo bạn là fan, mình không theo dõi nhóm nên thấy hơi dài.',
            ],
        ],

        'documentary' => [
            5 => [
                'Tư liệu quý, xem xong hiểu thêm rất nhiều điều.',
                'Cách kể chuyện cuốn hút, không hề khô khan như mình nghĩ.',
                'Hình ảnh chân thực, nhiều đoạn rất xúc động.',
            ],
            4 => [
                'Nội dung bổ ích, nhịp phim hơi chậm nhưng đáng xem.',
                'Nhiều tư liệu hiếm, phần phỏng vấn khá hay.',
            ],
            3 => [
                'Thông tin hay nhưng trình bày hơi đều đều, dễ buồn ngủ.',
                'Hợp với người quan tâm chủ đề này, người xem bình thường thấy hơi dài.',
            ],
            2 => [
                'Nội dung không có gì mới so với những gì đã có trên mạng.',
                'Phim khá rời rạc, xem xong không đọng lại nhiều.',
            ],
        ],

        'animation' => [
            5 => [
                'Hình vẽ đẹp như tranh, màu sắc dịu mắt, xem rất thư giãn.',
                'Dẫn con đi xem, bé cười suốt cả phim, người lớn cũng mê.',
                'Người lớn xem cũng thấm, có đoạn mình rơi nước mắt luôn.',
                'Lồng tiếng tốt, nhạc phim dễ thương, xem xong vẫn còn ngân nga.',
                'Nhân vật đáng yêu quá, ra rạp xem màn hình lớn đã hơn hẳn.',
            ],
            4 => [
                'Hoạt hình đẹp, thông điệp nhẹ nhàng, hợp đi cùng gia đình.',
                'Bé nhà mình rất thích, chỉ có đoạn giữa hơi chậm.',
                'Nhiều chi tiết hài hước, cả nhà cười rần rần.',
            ],
            3 => [
                'Nét vẽ đẹp nhưng cốt truyện hơi đơn giản, hợp với các bé hơn.',
                'Xem ổn, nhưng không ấn tượng bằng phần trước.',
                'Bé thích nhưng người lớn xem thì hơi dễ đoán.',
            ],
            2 => [
                'Truyện hơi rời rạc, bé nhà mình xem được nửa phim thì đòi về.',
                'Hình ảnh ổn nhưng nội dung nhạt, không có điểm nhấn.',
            ],
        ],

        'horror' => [
            5 => [
                'Sợ thật sự, jump scare dồn dập, cả rạp hét ầm lên.',
                'Không khí u ám, âm thanh dọa người cực đỉnh, về nhà không dám tắt đèn.',
                'Phim kinh dị hiếm hoi có cốt truyện chặt chẽ, cái kết bất ngờ.',
                'Diễn viên diễn ra được cái sợ, xem mà nín thở.',
            ],
            4 => [
                'Khá đáng sợ, nửa sau cuốn hơn nửa đầu.',
                'Âm thanh và bối cảnh làm tốt, chỉ có vài chỗ hơi dễ đoán.',
                'Đi xem cùng nhóm bạn rất vui, ai cũng giật mình mấy lần.',
            ],
            3 => [
                'Có vài cảnh hù khá ổn nhưng tổng thể chưa đủ sợ.',
                'Mở đầu hấp dẫn nhưng về cuối giải thích hơi vội.',
                'Kinh dị vừa phải, người nhát xem được.',
            ],
            2 => [
                'Hù dọa toàn bằng âm thanh to, không thấy sợ mà thấy giật mình thôi.',
                'Cốt truyện khó hiểu, nhiều chi tiết bỏ ngỏ không giải thích.',
                'Trailer đáng sợ hơn phim.',
            ],
        ],

        'comedy' => [
            5 => [
                'Cười từ đầu đến cuối, cả rạp vỗ tay rần rần.',
                'Miếng hài duyên, không bị nhảm, xem xong thấy vui cả ngày.',
                'Diễn viên phối hợp ăn ý, nhiều câu thoại đáng nhớ.',
            ],
            4 => [
                'Hài nhẹ nhàng, có cả đoạn cảm động, đáng xem cuối tuần.',
                'Nhiều tình huống buồn cười, chỉ có đoạn cuối hơi lê thê.',
                'Xem giải trí rất ổn, đi cùng bạn bè là hợp nhất.',
            ],
            3 => [
                'Có vài đoạn buồn cười nhưng nhiều miếng hài hơi cũ.',
                'Nửa đầu vui, nửa sau chuyển sang nghiêm túc hơi gượng.',
            ],
            2 => [
                'Miếng hài nhạt, cả rạp im re ở nhiều đoạn.',
                'Gượng ép quá, cười không nổi.',
            ],
        ],

        'action' => [
            5 => [
                'Cảnh hành động mãn nhãn, xem màn hình lớn đã cực.',
                'Kỹ xảo đẹp, âm thanh cháy nổ chân thật, đáng tiền vé.',
                'Nhịp phim nhanh, không có phút nào thấy chán.',
                'Diễn viên chính quá ngầu, các pha đánh đấm được dàn dựng đẹp.',
            ],
            4 => [
                'Hành động đã mắt, cốt truyện hơi đơn giản nhưng vẫn cuốn.',
                'Kỹ xảo tốt, chỉ có đoạn giữa hơi chậm.',
                'Xem giải trí rất ổn, hợp đi cuối tuần.',
            ],
            3 => [
                'Kỹ xảo được nhưng kịch bản còn nhiều chỗ chưa hợp lý.',
                'Đánh nhau nhiều quá, phần nội dung bị mờ nhạt.',
                'Xem một lần cho biết, không có gì quá đặc sắc.',
            ],
            2 => [
                'Kỹ xảo giả, cảnh hành động quay rung khó theo dõi.',
                'Dài và ồn ào, nội dung không đọng lại gì.',
            ],
        ],

        'thriller' => [
            5 => [
                'Căng thẳng từ đầu đến cuối, cú twist cuối phim quá đỉnh.',
                'Kịch bản chặt chẽ, đoán mãi không ra hung thủ.',
                'Diễn xuất thuyết phục, không khí nghẹt thở suốt phim.',
            ],
            4 => [
                'Nội dung cuốn hút, cái kết khá bất ngờ.',
                'Nhiều chi tiết cài cắm hay, nên xem kỹ từ đầu.',
                'Hồi hộp, chỉ tiếc phần giải thích cuối hơi dài.',
            ],
            3 => [
                'Mở đầu hấp dẫn nhưng về cuối hơi đuối.',
                'Twist đoán được từ giữa phim nên bớt bất ngờ.',
            ],
            2 => [
                'Nội dung khá dễ đoán, xem nửa đầu là biết kết.',
                'Nhiều tình tiết vô lý, nhân vật hành động khó hiểu.',
            ],
        ],

        'romance' => [
            5 => [
                'Ngọt ngào và cảm động, xem xong muốn yêu ngay.',
                'Hai diễn viên chính quá đẹp đôi, phản ứng hóa học tuyệt vời.',
                'Nhạc phim hay, nhiều cảnh lãng mạn đẹp như MV.',
            ],
            4 => [
                'Câu chuyện dễ thương, hợp đi xem cùng người yêu.',
                'Nhẹ nhàng, có cả đoạn hài lẫn đoạn khóc.',
            ],
            3 => [
                'Dễ thương nhưng motip quen thuộc, đoán trước được kết.',
                'Nửa đầu ngọt, nửa sau drama hơi nhiều.',
            ],
            2 => [
                'Tình tiết sến và kéo dài không cần thiết.',
                'Hai nhân vật chính thiếu cảm xúc, xem không thấy thuyết phục.',
            ],
        ],

        'drama' => [
            5 => [
                'Phim chạm đến cảm xúc, cả rạp sụt sùi ở đoạn cuối.',
                'Diễn xuất quá đạt, nhiều cảnh nhìn ánh mắt thôi đã muốn khóc.',
                'Câu chuyện ý nghĩa, xem xong muốn gọi điện ngay cho bố mẹ.',
            ],
            4 => [
                'Nội dung sâu sắc, nhịp hơi chậm nhưng đáng xem.',
                'Diễn viên diễn tốt, kịch bản có chiều sâu.',
                'Nhiều khoảnh khắc ấm áp, hợp đi xem cùng gia đình.',
            ],
            3 => [
                'Thông điệp hay nhưng cách kể hơi lê thê.',
                'Có vài cảnh cảm động, tổng thể chưa thật sự bùng nổ.',
            ],
            2 => [
                'Phim kéo dài không cần thiết, nhiều đoạn lặp lại.',
                'Cố lấy nước mắt khán giả quá, xem thấy gượng.',
            ],
        ],
    ],

    'general' => [
        5 => [
            'Rất đáng tiền vé, nhất định sẽ rủ thêm bạn đi xem lần nữa.',
            'Ra rạp xem mới cảm nhận hết được, quá hài lòng.',
            '10 điểm, không có gì để chê.',
        ],
        4 => [
            'Hay, đáng xem, chỉ có vài điểm nhỏ chưa ưng.',
            'Xem ổn, không uổng công ra rạp.',
        ],
        3 => [
            'Tạm ổn, xem một lần cho biết.',
            'Không như kỳ vọng lắm nhưng cũng không tệ.',
        ],
        2 => [
            'Hơi thất vọng so với kỳ vọng.',
            'Không hợp gu mình lắm.',
        ],
    ],
];
