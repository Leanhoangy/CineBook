<?php

namespace Database\Seeders;

use App\Models\Genre;
use Illuminate\Database\Seeder;

class GenreSeeder extends Seeder
{
    public function run(): void
    {
        $genres = [
            'Hành động' => 'Những pha rượt đuổi, cháy nổ và đối đầu nghẹt thở.',
            'Phiêu lưu' => 'Hành trình khám phá những vùng đất mới.',
            'Hoạt hình' => 'Phim hoạt hình phù hợp cho cả gia đình.',
            'Hài' => 'Tiếng cười sảng khoái sau giờ làm việc.',
            'Kinh dị' => 'Những câu chuyện rùng rợn, ám ảnh.',
            'Khoa học viễn tưởng' => 'Tương lai, công nghệ và vũ trụ.',
            'Tâm lý' => 'Câu chuyện sâu lắng về con người và cuộc sống.',
            'Tình cảm' => 'Những chuyện tình lãng mạn, cảm động.',
            'Gia đình' => 'Phim phù hợp xem cùng người thân.',
            'Tội phạm' => 'Điều tra, phá án và thế giới ngầm.',
            'Hồi hộp' => 'Căng thẳng, gay cấn đến phút cuối.',
            'Bí ẩn' => 'Những câu đố và bí mật chờ được giải mã.',
            'Giả tưởng' => 'Thế giới phép thuật và sinh vật kỳ ảo.',
            'Lịch sử' => 'Tái hiện những sự kiện và nhân vật lịch sử.',
            'Chiến tranh' => 'Khốc liệt nơi chiến trường.',
            'Âm nhạc' => 'Giai điệu và những câu chuyện âm nhạc.',
            'Tài liệu' => 'Câu chuyện có thật được ghi lại chân thực.',
        ];

        foreach ($genres as $name => $description) {
            Genre::create(['name' => $name, 'description' => $description]);
        }
    }
}
