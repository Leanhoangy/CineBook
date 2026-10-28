<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\RefundFailedException;
use App\Exports\BookingsExport;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\BookingRefunder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function index(Request $request): View
    {
        $bookings = $this->filtered($request)
            ->with(['user', 'showtime.movie', 'showtime.room', 'seats'])
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $pendingRefunds = Booking::pendingRefund()->count();

        return view('admin.bookings.index', compact('bookings', 'pendingRefunds'));
    }

    /** Xuất các đơn theo bộ lọc đang xem ra file Excel */
    public function export(Request $request): BinaryFileResponse
    {
        $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $path = sys_get_temp_dir().'/'.Str::uuid().'.xlsx';
        (new BookingsExport($this->filtered($request)))->store($path);

        $range = $request->query('from') || $request->query('to')
            ? '_'.($request->query('from') ?: 'dau').'_den_'.($request->query('to') ?: now()->toDateString())
            : '_'.now()->format('Y-m-d');

        return response()->download($path, "don-dat-ve{$range}.xlsx")->deleteFileAfterSend();
    }

    /** Bộ lọc dùng chung cho trang danh sách và file Excel */
    private function filtered(Request $request): Builder
    {
        return Booking::query()
            ->search($request->query('q'))
            ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))
            ->when($request->query('payment'), fn ($q, $p) => match ($p) {
                'unpaid' => $q->whereNull('paid_at'),
                'refund_pending' => $q->pendingRefund(),
                'refunded' => $q->whereNotNull('refunded_at'),
                default => $q->where('payment_method', $p),
            })
            ->when($request->query('from'), fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($request->query('to'), fn ($q, $d) => $q->whereDate('created_at', '<=', $d));
    }

    public function show(Booking $booking): View
    {
        $booking->load(['user', 'showtime.movie', 'showtime.room', 'seats', 'combos', 'voucher']);

        return view('admin.bookings.show', compact('booking'));
    }

    /** Cập nhật trạng thái vé (xác nhận / hủy) */
    public function update(Request $request, Booking $booking): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys(Booking::STATUSES))],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validated['status'] === Booking::STATUS_CONFIRMED && ! $booking->isConfirmed()) {
            if ($booking->isRefunded() || $booking->showtime->isCancelled()) {
                return back()->with('error', 'Không thể khôi phục vé đã hoàn tiền hoặc thuộc suất chiếu đã bị hủy.');
            }
            $conflict = array_intersect(
                $booking->seats->pluck('seat_code')->all(),
                $booking->showtime->bookedSeatCodes(),
            );

            if ($conflict) {
                return back()->with('error', 'Không thể khôi phục vé vì ghế '.implode(', ', $conflict).' đã có người khác đặt.');
            }
        }

        $validated['cancel_reason'] = $validated['status'] === Booking::STATUS_CANCELLED
            ? ($booking->isConfirmed() ? Booking::CANCEL_BY_ADMIN : $booking->cancel_reason)
            : null;
        $booking->update($validated);

        return back()->with('success', "Đã cập nhật vé {$booking->code}: {$booking->status_label}.");
    }

    /**
     * Hủy vé và hoàn tiền (chỉ dùng khi lỗi từ phía rạp, hoặc khách trả tiền cho vé đã bị hủy).
     * Vé VNPay hoàn qua API VNPay; vé trả tại quầy (hoặc khi VNPay lỗi) ghi nhận hoàn thủ công.
     */
    public function refund(Request $request, Booking $booking, BookingRefunder $refunder): RedirectResponse
    {
        $validated = $request->validate([
            'method' => ['required', Rule::in(array_keys(Booking::REFUND_METHODS))],
            'refund_note' => ['required_if:method,'.Booking::REFUND_MANUAL, 'nullable', 'string', 'max:100'],
        ], [
            'refund_note.required_if' => 'Hãy ghi lại cách đã trả tiền cho khách (VD: Trả tiền mặt tại quầy, chuyển khoản Vietcombank...).',
        ]);

        try {
            $refunder->refund($booking, $validated['method'], $validated['refund_note'] ?? null, $request->user(), $request->ip());
        } catch (RefundFailedException $e) {
            return back()->with('error', 'Chưa hoàn được tiền: '.$e->getMessage())->with('refund_failed', true);
        }

        return back()->with('success', 'Đã hoàn '.number_format($booking->total_price, 0, ',', '.')." ₫ cho vé {$booking->code} và gửi email báo khách.");
    }

    /** Nhân viên xác nhận đã thu tiền khi khách trả tại quầy */
    public function markPaid(Booking $booking): RedirectResponse
    {
        if (! $booking->isConfirmed() || $booking->isPaid()) {
            return back()->with('error', "Vé {$booking->code} đã thanh toán hoặc đã hủy.");
        }

        $booking->markPaid(Booking::PAYMENT_COUNTER);

        return back()->with('success', "Đã ghi nhận vé {$booking->code} thanh toán tại quầy.");
    }

    public function destroy(Booking $booking): RedirectResponse
    {
        if ($booking->isConfirmed()) {
            return back()->with('error', 'Chỉ được xóa vé đã hủy. Hãy hủy vé trước khi xóa.');
        }

        // Vé đã có tiền ra/vào là chứng từ kế toán, không được xóa
        if ($booking->isPaid()) {
            return back()->with('error', 'Không thể xóa vé đã thanh toán (kể cả đã hoàn tiền) vì cần lưu lại lịch sử giao dịch.');
        }

        $booking->delete();

        return redirect()->route('admin.bookings.index')->with('success', "Đã xóa vé {$booking->code}.");
    }
}
