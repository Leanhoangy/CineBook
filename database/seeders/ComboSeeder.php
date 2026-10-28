<?php

namespace Database\Seeders;

use App\Models\Combo;
use Illuminate\Database\Seeder;

/** Bắp nước bán kèm vé, giá tham khảo các rạp tại TP.HCM */
class ComboSeeder extends Seeder
{
    public function run(): void
    {
        $combos = [
            ['name' => 'Combo Solo', 'description' => '1 bắp rang bơ (L) + 1 nước ngọt (L)', 'price' => 85000],
            ['name' => 'Combo Couple', 'description' => '1 bắp rang bơ (L) + 2 nước ngọt (L)', 'price' => 115000],
            ['name' => 'Combo Gia đình', 'description' => '2 bắp rang bơ (L) + 4 nước ngọt (L) + 1 snack', 'price' => 219000],
            ['name' => 'Bắp rang bơ (L)', 'description' => 'Vị bơ hoặc caramel', 'price' => 59000],
            ['name' => 'Nước ngọt (L)', 'description' => 'Nước ngọt có ga các loại, 32oz', 'price' => 35000],
            ['name' => 'Combo Halloween', 'description' => 'Bắp phô mai + 2 nước + ly hình bí ngô (đã hết hàng)', 'price' => 149000, 'is_active' => false],
        ];

        foreach ($combos as $combo) {
            Combo::create($combo);
        }
    }
}
