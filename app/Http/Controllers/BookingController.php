<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookingRequest;
use App\Mail\BookingConfirmed;
use App\Models\Booking;
use App\Models\Combo;
use App\Models\Showtime;
use App\Models\Voucher;
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
    public function create(Request $request, Showtime $showtime): View|RedirectResponse
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

        // Gợi ý các mã giảm giá khách còn dùng được (dữ liệu để trình duyệt tự tính mức giảm khi chọn ghế)
        $vouchers = Voucher::usableBy($request->user())->map(fn (Voucher $v) => [
            'code' => $v->code,
            'type' => $v->type,
            'value' => $v->value,
            'max_discount' => $v->max_discount,
            'min_order' => $v->min_order,
            'label' => $v->label,
            'description' => $v->description,
            'remaining' => $v->usage_limit !== null ? $v->usage_limit - $v->used_count : null,
        ]);

        $combos = Combo::active()->orderBy('id')->get();

        return view('bookings.create', compact('showtime', 'bookedSeats', 'otherShowtimes', 'vouchers', 'combos'));
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

            $seats = array_map(function (string $code) use ($showtime) {
                $type = $showtime->room->seatType($code);

                return ['seat_code' => $code, 'seat_type' => $type, 'price' => $showtime->seatPrice($type)];
            }, $seatCodes);
            $subtotal = array_sum(array_column($seats, 'price'));

            // Khóa mã giảm giá để 2 đơn cùng lúc không vượt quá số lượt cho phép
            $voucher = null;
            $discount = 0;
            if ($code = $request->validated('voucher_code')) {
                $voucher = Voucher::where('code', strtoupper(trim($code)))->lockForUpdate()->first();
                $reason = $voucher ? $voucher->unavailableReason($request->user(), $subtotal) : 'Mã giảm giá không tồn tại.';

                if ($reason) {
                    throw ValidationException::withMessages(['voucher_code' => $reason]);
                }
                $discount = $voucher->discountFor($subtotal);
            }

            // Bắp nước: lấy giá hiện tại từ CSDL (không tin giá gửi từ trình duyệt); mã giảm giá chỉ trừ vào tiền vé
            $quantities = array_filter((array) $request->validated('combos', []));
            $combos = Combo::active()->whereIn('id', array_keys($quantities))->get()
                ->map(fn (Combo $combo) => [
                    'combo_id' => $combo->id,
                    'name' => $combo->name,
                    'price' => $combo->price,
                    'quantity' => (int) $quantities[$combo->id],
                ]);
            $comboTotal = $combos->sum(fn (array $c) => $c['price'] * $c['quantity']);

            $booking = Booking::create([
                'user_id' => $request->user()->id,
                'showtime_id' => $showtime->id,
                'voucher_id' => $voucher?->id,
                'discount' => $discount,
                'total_price' => $subtotal - $discount + $comboTotal,
                'note' => $request->validated('note'),
            ]);
            $booking->seats()->createMany($seats);
            $booking->combos()->createMany($combos->all());

            return $booking;
        });

        // Lỗi gửi email (VD: chưa cấu hình SMTP) không được làm hỏng việc đặt vé đã thành công
        try {
            Mail::to($request->user())->send(new BookingConfirmed($booking));
        } catch (\Throwable $e) {
            report($e);
        }

        return redirect()->route('bookings.show', $booking)
            ->with('success', 'Đặt vé thành công! Mã vé của bạn là '.$booking->code.'. Bạn có thể thanh toán online ngay hoặc trả tại quầy khi nhận vé.');
    }

    public function show(Booking $booking): View
    {
        Gate::authorize('view', $booking);

        $booking->load(['showtime.movie', 'showtime.room', 'seats', 'combos', 'user', 'voucher']);

        return view('bookings.show', compact('booking'));
    }

    public function cancel(Booking $booking): RedirectResponse
    {
        if (Gate::denies('cancel', $booking)) {
            return back()->with('error', $booking->isPaid()
                ? 'Vé đã thanh toán online không hỗ trợ hủy. Vui lòng liên hệ hỗ trợ nếu cần.'
                : 'Vé chỉ được hủy trước giờ chiếu ít nhất '.config('cinema.cancel_before_minutes').' phút.');
        }

        $booking->cancel(Booking::CANCEL_BY_CUSTOMER);

        return redirect()->route('bookings.show', $booking)->with('success', 'Đã hủy vé '.$booking->code.'. Ghế đã được mở lại cho khách khác.');
    }

    /**
     * API kiểm tra mã giảm giá trên trang chọn ghế (AJAX).
     * Trả về điều kiện của mã để trình duyệt tự tính lại khi khách đổi ghế; khi đặt vé server sẽ kiểm tra lại.
     */
    public function checkVoucher(Request $request, Showtime $showtime): JsonResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'max:30'],
            'seats' => ['array', 'max:'.config('cinema.max_seats_per_booking')],
            'seats.*' => ['string'],
        ]);

        $subtotal = collect($request->input('seats', []))
            ->filter(fn ($code) => $showtime->room->hasSeat($code))
            ->sum(fn ($code) => $showtime->seatPrice($showtime->room->seatType($code)));

        $voucher = Voucher::findByCode($request->input('code'));
        $reason = $voucher ? $voucher->unavailableReason($request->user(), $subtotal) : 'Mã giảm giá không tồn tại.';

        // Chưa chọn ghế / chưa đủ giá trị tối thiểu vẫn cho áp mã, trình duyệt sẽ nhắc khi tính tiền
        $belowMinimum = $voucher && $subtotal < $voucher->min_order;
        if ($reason && ! $belowMinimum) {
            return response()->json(['valid' => false, 'message' => $reason], 422);
        }

        return response()->json([
            'valid' => true,
            'code' => $voucher->code,
            'label' => $voucher->label,
            'type' => $voucher->type,
            'value' => $voucher->value,
            'max_discount' => $voucher->max_discount,
            'min_order' => $voucher->min_order,
            'discount' => $belowMinimum ? 0 : $voucher->discountFor($subtotal),
        ]);
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
