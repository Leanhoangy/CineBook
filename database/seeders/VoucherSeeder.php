<?php

namespace Database\Seeders;

use App\Models\Voucher;
use Illuminate\Database\Seeder;

class VoucherSeeder extends Seeder
{
    public function run(): void
    {
        $vouchers = [
            ['code' => 'CINEBOOK10', 'description' => 'Giảm 10% cho mọi đơn đặt vé', 'type' => 'percent', 'value' => 10, 'max_discount' => 30000,
                'starts_at' => now()->subMonths(2), 'expires_at' => now()->addMonths(3)],
            ['code' => 'GIAM20K', 'description' => 'Giảm 20.000 ₫ cho đơn từ 150.000 ₫', 'type' => 'fixed', 'value' => 20000, 'min_order' => 150000, 'usage_limit' => 500,
                'starts_at' => now()->subMonth(), 'expires_at' => now()->addMonths(2)],
            ['code' => 'SINHVIEN', 'description' => 'Ưu đãi học sinh – sinh viên', 'type' => 'percent', 'value' => 15, 'max_discount' => 25000,
                'starts_at' => now()->subWeeks(3), 'expires_at' => now()->addMonths(6)],
            ['code' => 'NHOMBAN4', 'description' => 'Đi nhóm từ 4 người (đơn từ 300.000 ₫)', 'type' => 'fixed', 'value' => 50000, 'min_order' => 300000, 'usage_limit' => 100,
                'starts_at' => now()->subWeeks(2), 'expires_at' => now()->addMonth()],
            ['code' => 'THANHVIENMOI', 'description' => 'Giảm 30% cho 20 khách đầu tiên', 'type' => 'percent', 'value' => 30, 'max_discount' => 50000, 'usage_limit' => 20,
                'starts_at' => now()->subWeeks(2), 'expires_at' => now()->addMonth()],
            // Mã đã hết hạn và mã chưa tới ngày, dùng để demo thông báo lỗi
            ['code' => 'HE2026', 'description' => 'Khuyến mãi hè 2026 (đã kết thúc)', 'type' => 'percent', 'value' => 20, 'max_discount' => 40000,
                'starts_at' => now()->subMonths(4), 'expires_at' => now()->subMonths(2)],
            ['code' => 'GIANGSINH', 'description' => 'Ưu đãi Giáng sinh', 'type' => 'fixed', 'value' => 30000,
                'starts_at' => now()->addWeeks(6), 'expires_at' => now()->addWeeks(8)],
        ];

        foreach ($vouchers as $voucher) {
            Voucher::create($voucher);
        }
    }
}
