<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Showtime;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Báo cáo kinh doanh theo khoảng ngày CHIẾU (chỉ tính suất đã bắt đầu, vé đã xác nhận):
 * doanh thu vé / bắp nước, tỉ lệ lấp đầy ghế, hiệu quả từng khung giờ và từng phim.
 */
class SalesReport
{
    /** Khung giờ chiếu: [nhãn, giờ bắt đầu, giờ kết thúc) */
    public const SLOTS = [
        'morning' => ['Sáng (trước 12h)', 0, 12],
        'afternoon' => ['Chiều (12h – 17h)', 12, 17],
        'evening' => ['Tối (17h – 21h)', 17, 21],
        'late' => ['Khuya (từ 21h)', 21, 24],
    ];

    /** @var Collection<int, object> Mỗi suất chiếu kèm số ghế, vé bán, doanh thu */
    private Collection $showtimes;

    public function __construct(public readonly Carbon $from, public readonly Carbon $to)
    {
        $this->showtimes = $this->loadShowtimes();
    }

    private function loadShowtimes(): Collection
    {
        $confirmed = fn ($q) => $q->where('bookings.status', Booking::STATUS_CONFIRMED);

        $seats = DB::table('booking_seats')->join('bookings', 'bookings.id', '=', 'booking_seats.booking_id')
            ->tap($confirmed)->groupBy('bookings.showtime_id')
            ->selectRaw('bookings.showtime_id, COUNT(*) as seats');
        $orders = DB::table('bookings')->tap($confirmed)->groupBy('showtime_id')
            ->selectRaw('showtime_id, COUNT(*) as orders, SUM(total_price) as revenue');
        $combos = DB::table('booking_combos')->join('bookings', 'bookings.id', '=', 'booking_combos.booking_id')
            ->tap($confirmed)->groupBy('bookings.showtime_id')
            ->selectRaw('bookings.showtime_id, SUM(booking_combos.price * booking_combos.quantity) as combo_revenue, COUNT(DISTINCT bookings.id) as combo_orders');

        return Showtime::query()
            ->join('rooms', 'rooms.id', '=', 'showtimes.room_id')
            ->join('movies', 'movies.id', '=', 'showtimes.movie_id')
            ->leftJoinSub($seats, 's', 's.showtime_id', '=', 'showtimes.id')
            ->leftJoinSub($orders, 'o', 'o.showtime_id', '=', 'showtimes.id')
            ->leftJoinSub($combos, 'c', 'c.showtime_id', '=', 'showtimes.id')
            ->whereBetween('showtimes.start_time', [$this->from->copy()->startOfDay(), min($this->to->copy()->endOfDay(), now())])
            ->select([
                'showtimes.id', 'showtimes.start_time', 'showtimes.movie_id', 'movies.title',
                DB::raw('rooms.total_rows * rooms.seats_per_row as capacity'),
                DB::raw('COALESCE(s.seats, 0) as seats'),
                DB::raw('COALESCE(o.orders, 0) as orders'),
                DB::raw('COALESCE(o.revenue, 0) as revenue'),
                DB::raw('COALESCE(c.combo_revenue, 0) as combo_revenue'),
                DB::raw('COALESCE(c.combo_orders, 0) as combo_orders'),
            ])
            ->toBase()
            ->get()
            ->map(function (object $st) {
                $st->start_time = Carbon::parse($st->start_time);
                foreach (['capacity', 'seats', 'orders', 'revenue', 'combo_revenue', 'combo_orders'] as $field) {
                    $st->{$field} = (int) $st->{$field};
                }

                return $st;
            });
    }

    /** Cộng dồn một nhóm suất chiếu thành các chỉ số */
    private static function summarize(Collection $group): array
    {
        $capacity = $group->sum('capacity');
        $revenue = $group->sum('revenue');
        $combo = $group->sum('combo_revenue');
        $orders = $group->sum('orders');

        return [
            'showtimes' => $group->count(),
            'seats' => $group->sum('seats'),
            'capacity' => $capacity,
            'occupancy' => $capacity ? round($group->sum('seats') / $capacity * 100, 1) : 0.0,
            'orders' => $orders,
            'revenue' => $revenue,
            'combo_revenue' => $combo,
            'ticket_revenue' => $revenue - $combo,
            'combo_rate' => $orders ? (int) round($group->sum('combo_orders') / $orders * 100) : 0,
        ];
    }

    public function totals(): array
    {
        return self::summarize($this->showtimes);
    }

    /** Doanh thu từng ngày chiếu (đủ mọi ngày trong khoảng, ngày không có suất = 0) */
    public function daily(): Collection
    {
        $byDay = $this->showtimes->groupBy(fn ($st) => $st->start_time->toDateString());
        $last = min($this->to->copy()->startOfDay(), today());

        return collect(Carbon::parse($this->from)->startOfDay()->daysUntil($last))
            ->map(fn (Carbon $day) => [
                'label' => $day->format('d/m'),
                'value' => (int) ($byDay->get($day->toDateString())?->sum('revenue') ?? 0),
            ]);
    }

    /** Hiệu quả từng khung giờ + phim bán nhiều vé nhất trong khung giờ đó */
    public function bySlot(): Collection
    {
        return collect(self::SLOTS)->map(function (array $slot) {
            [$label, $fromHour, $toHour] = $slot;
            $group = $this->showtimes->filter(fn ($st) => $st->start_time->hour >= $fromHour && $st->start_time->hour < $toHour);
            $top = $group->groupBy('movie_id')->map(fn (Collection $g) => ['title' => $g->first()->title, 'seats' => $g->sum('seats')])
                ->sortByDesc('seats')->first();

            return ['label' => $label, ...self::summarize($group), 'top_movie' => ($top['seats'] ?? 0) ? $top : null];
        });
    }

    /** Phim theo doanh thu, kèm tỉ lệ lấp đầy */
    public function byMovie(int $limit = 10): Collection
    {
        return $this->showtimes->groupBy('movie_id')
            ->map(fn (Collection $g) => ['title' => $g->first()->title, ...self::summarize($g)])
            ->filter(fn (array $m) => $m['orders'] > 0)
            ->sortByDesc('revenue')
            ->take($limit)
            ->values();
    }

    /** Bắp nước bán chạy: số phần và doanh thu theo tên món */
    public function combos(): Collection
    {
        return DB::table('booking_combos')
            ->join('bookings', 'bookings.id', '=', 'booking_combos.booking_id')
            ->whereIn('bookings.showtime_id', $this->showtimes->pluck('id'))
            ->where('bookings.status', Booking::STATUS_CONFIRMED)
            ->groupBy('booking_combos.name')
            ->selectRaw('booking_combos.name, SUM(booking_combos.quantity) as quantity, SUM(booking_combos.price * booking_combos.quantity) as revenue')
            ->orderByDesc('revenue')
            ->get();
    }
}
