<?php

namespace App\Http\Controllers\Admin;

use App\Exports\BookingsExport;
use App\Http\Controllers\Controller;
use App\Models\Booking;
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

        return view('admin.bookings.index', compact('bookings'));
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
            ->when($request->query('from'), fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($request->query('to'), fn ($q, $d) => $q->whereDate('created_at', '<=', $d));
    }

    public function show(Booking $booking): View
    {
        $booking->load(['user', 'showtime.movie', 'showtime.room', 'seats', 'voucher']);

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
            $conflict = array_intersect(
                $booking->seats->pluck('seat_code')->all(),
                $booking->showtime->bookedSeatCodes(),
            );

            if ($conflict) {
                return back()->with('error', 'Không thể khôi phục vé vì ghế '.implode(', ', $conflict).' đã có người khác đặt.');
            }
        }

        $booking->update($validated);

        return back()->with('success', "Đã cập nhật vé {$booking->code}: {$booking->status_label}.");
    }

    public function destroy(Booking $booking): RedirectResponse
    {
        if ($booking->isConfirmed()) {
            return back()->with('error', 'Chỉ được xóa vé đã hủy. Hãy hủy vé trước khi xóa.');
        }

        $booking->delete();

        return redirect()->route('admin.bookings.index')->with('success', "Đã xóa vé {$booking->code}.");
    }
}
