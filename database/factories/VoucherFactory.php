<?php

namespace Database\Factories;

use App\Models\Voucher;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Voucher>
 */
class VoucherFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('CB####??')),
            'description' => fake()->sentence(4),
            'type' => Voucher::TYPE_PERCENT,
            'value' => 10,
            'max_discount' => null,
            'min_order' => 0,
            'usage_limit' => null,
            'starts_at' => now()->subDay(),
            'expires_at' => now()->addMonth(),
            'is_active' => true,
        ];
    }

    public function fixed(int $amount): static
    {
        return $this->state(fn () => ['type' => Voucher::TYPE_FIXED, 'value' => $amount, 'max_discount' => null]);
    }

    public function expired(): static
    {
        return $this->state(fn () => ['starts_at' => now()->subMonth(), 'expires_at' => now()->subDay()]);
    }
}
