<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Soát vé tại cửa phòng chiếu: quét QR trên vé (camera hoặc máy quét) hoặc gõ mã vé,
 * xem thông tin vé rồi xác nhận cho vào. Mỗi vé chỉ được soát 1 lần.
 */
class CheckInController extends Controller
{
    private const RELATIONS = ['user', 'showtime.movie', 'showtime.room', 'seats'];

    public function index(): View
    {
        $recent = Booking::with(self::RELATIONS)
            ->whereDate('checked_in_at', today())
            ->latest('checked_in_at')
            ->take(10)
            ->get();
        $todayCount = Booking::whereDate('checked_in_at', today())->count();

        return view('admin.check-in.index', compact('recent', 'todayCount'));
    }

    /** Tra cứu vé theo mã (AJAX) */
    public function lookup(Request $request): JsonResponse
    {
        $request->validate(['code' => ['required', 'string', 'max:30']]);

        $code = strtoupper(trim($request->query('code')));
        $booking = Booking::with(self::RELATIONS)->where('code', $code)->first();

        if (! $booking) {
            return response()->json(['ok' => false, 'html' => view('admin.check-in._not-found', compact('code'))->render()], 404);
        }

        return response()->json([
            'ok' => $booking->checkInProblem() === null,
            'html' => view('admin.check-in._result', compact('booking'))->render(),
        ]);
    }

    /** Xác nhận cho vào rạp; vé chưa thanh toán thì phải xác nhận đã thu tiền ($collect) */
    public function store(Request $request, Booking $booking): JsonResponse
    {
        $collect = $request->boolean('collect');

        $error = DB::transaction(function () use ($booking, $collect) {
            // Khóa dòng để 2 nhân viên quét cùng 1 vé cùng lúc thì chỉ 1 người cho vào được
            $booking = Booking::lockForUpdate()->find($booking->id);

            if ($problem = $booking->checkInProblem()) {
                return $problem;
            }
            if (! $booking->isPaid() && ! $collect) {
                return 'Vé chưa thanh toán. Hãy thu tiền trước khi cho khách vào.';
            }

            if (! $booking->isPaid()) {
                $booking->markPaid(Booking::PAYMENT_COUNTER);
            }
            $booking->update(['checked_in_at' => now()]);

            return null;
        });

        $booking = $booking->fresh(self::RELATIONS);
        $html = view('admin.check-in._result', ['booking' => $booking, 'error' => $error, 'justCheckedIn' => ! $error])->render();

        if ($error) {
            return response()->json(['ok' => false, 'html' => $html], 422);
        }

        return response()->json([
            'ok' => true,
            'html' => $html,
            'row' => view('admin.check-in._row', compact('booking'))->render(),
            'count' => Booking::whereDate('checked_in_at', today())->count(),
        ]);
    }
}
