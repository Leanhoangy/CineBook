<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookingRequest;
use App\Mail\BookingConfirmed;
use App\Models\Booking;
use App\Models\Showtime;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BookingController extends Controller
{
    /** Lịch sử đặt vé của khách */
    public function index(Request $request): View
    {
        $tab = $request->query('tab') === 'da-xem' ? 'past' : 'upcoming';

        $bookings = $request->user()->bookings()
            ->with(['showtime.movie', 'showtime.room', 'seats'])
            ->whereHas('showtime', fn ($q) => $tab === 'past'
                ? $q->where('start_time', '<=', now())
                : $q->where('start_time', '>', now()))
            ->latest()
            ->paginate(6)
            ->withQueryString();

        return view('bookings.index', compact('bookings', 'tab'));
    }

    /** Trang chọn ghế */
    public function create(Showtime $showtime): View|RedirectResponse
    {
        $showtime->load(['movie.genres', 'room']);

        if (! $showtime->isBookable()) {
            return redirect()->route('movies.show', $showtime->movie)
                ->with('error', 'Suất chiếu này đã bắt đầu hoặc không còn nhận đặt vé.');
        }

        $bookedSeats = $showtime->bookedSeatCodes();

        $otherShowtimes = $showtime->movie->upcomingShowtimes()
            ->with('room')
            ->whereDate('start_time', $showtime->start_time)
            ->get();

        return view('bookings.create', compact('showtime', 'bookedSeats', 'otherShowtimes'));
    }

    /** Lưu đặt vé, dùng transaction + khóa dòng để tránh 2 người đặt trùng ghế */
    public function store(StoreBookingRequest $request, Showtime $showtime): RedirectResponse
    {
        $seatCodes = array_values(array_unique($request->validated('seats')));

        $booking = DB::transaction(function () use ($request, $showtime, $seatCodes) {
            // Khóa suất chiếu: các yêu cầu đặt vé cùng suất phải xếp hàng chờ nhau
            $showtime = Showtime::with(['room', 'movie'])->lockForUpdate()->findOrFail($showtime->id);

            if (! $showtime->isBookable()) {
                throw ValidationException::withMessages(['seats' => 'Suất chiếu này không còn nhận đặt vé.']);
            }

            $taken = array_intersect($seatCodes, $showtime->bookedSeatCodes());
            if ($taken) {
                throw ValidationException::withMessages([
                    'seats' => 'Ghế '.implode(', ', $taken).' vừa có người đặt. Vui lòng chọn ghế khác.',
                ]);
            }

            $booking = Booking::create([
                'user_id' => $request->user()->id,
                'showtime_id' => $showtime->id,
                'total_price' => 0,
                'note' => $request->validated('note'),
            ]);

            $total = 0;
            foreach ($seatCodes as $code) {
                $type = $showtime->room->seatType($code);
                $price = $showtime->seatPrice($type);
                $total += $price;

                $booking->seats()->create(['seat_code' => $code, 'seat_type' => $type, 'price' => $price]);
            }

            $booking->update(['total_price' => $total]);

            return $booking;
        });

        // Lỗi gửi email (VD: chưa cấu hình SMTP) không được làm hỏng việc đặt vé đã thành công
        try {
            Mail::to($request->user())->send(new BookingConfirmed($booking));
        } catch (\Throwable $e) {
            report($e);
        }

        return redirect()->route('bookings.show', $booking)
            ->with('success', 'Đặt vé thành công! Mã vé của bạn là '.$booking->code.'.');
    }

    public function show(Booking $booking): View
    {
        Gate::authorize('view', $booking);

        $booking->load(['showtime.movie', 'showtime.room', 'seats', 'user']);

        return view('bookings.show', compact('booking'));
    }

    public function cancel(Booking $booking): RedirectResponse
    {
        if (Gate::denies('cancel', $booking)) {
            return back()->with('error', 'Vé chỉ được hủy trước giờ chiếu ít nhất '.config('cinema.cancel_before_minutes').' phút.');
        }

        $booking->update(['status' => Booking::STATUS_CANCELLED]);

        return redirect()->route('bookings.show', $booking)->with('success', 'Đã hủy vé '.$booking->code.'. Ghế đã được mở lại cho khách khác.');
    }

    /** API trả về danh sách ghế đã đặt để cập nhật sơ đồ ghế theo thời gian thực (AJAX) */
    public function seatStatus(Showtime $showtime): JsonResponse
    {
        return response()->json([
            'booked' => $showtime->bookedSeatCodes(),
            'updated_at' => now()->format('H:i:s'),
        ]);
    }
}
