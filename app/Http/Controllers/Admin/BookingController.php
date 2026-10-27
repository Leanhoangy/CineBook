<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function index(Request $request): View
    {
        $bookings = Booking::with(['user', 'showtime.movie', 'showtime.room', 'seats'])
            ->search($request->query('q'))
            ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))
            ->when($request->query('from'), fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($request->query('to'), fn ($q, $d) => $q->whereDate('created_at', '<=', $d))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.bookings.index', compact('bookings'));
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
