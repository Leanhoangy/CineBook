<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RoomRequest;
use App\Models\Room;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RoomController extends Controller
{
    public function index(Request $request): View
    {
        $rooms = Room::withCount(['showtimes as upcoming_count' => fn ($q) => $q->upcoming()])
            ->when($request->query('q'), fn ($q, $k) => $q->where('name', 'like', "%{$k}%"))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('admin.rooms.index', compact('rooms'));
    }

    public function create(): View
    {
        return view('admin.rooms.create', ['room' => new Room(['total_rows' => 8, 'seats_per_row' => 12, 'vip_rows' => 2, 'is_active' => true])]);
    }

    public function store(RoomRequest $request): RedirectResponse
    {
        $room = Room::create([...$request->validated(), 'is_active' => $request->boolean('is_active')]);

        return redirect()->route('admin.rooms.index')->with('success', "Đã thêm {$room->name} ({$room->capacity} ghế).");
    }

    public function edit(Room $room): View
    {
        return view('admin.rooms.edit', compact('room'));
    }

    public function update(RoomRequest $request, Room $room): RedirectResponse
    {
        $data = [...$request->validated(), 'is_active' => $request->boolean('is_active')];

        // Không cho thu nhỏ sơ đồ ghế khi phòng đã có vé cho các suất sắp tới
        $shrinking = $data['total_rows'] < $room->total_rows || $data['seats_per_row'] < $room->seats_per_row;
        $hasTickets = $room->showtimes()->upcoming()->whereHas('confirmedBookings')->exists();

        if ($shrinking && $hasTickets) {
            return back()->withInput()->with('error', 'Phòng đã có vé cho các suất sắp tới nên không thể giảm số hàng hoặc số ghế.');
        }

        $room->update($data);

        return redirect()->route('admin.rooms.index')->with('success', "Đã cập nhật {$room->name}.");
    }

    public function destroy(Room $room): RedirectResponse
    {
        if ($room->showtimes()->exists()) {
            return back()->with('error', "Không thể xóa {$room->name} vì đã có suất chiếu. Bạn có thể chuyển phòng sang trạng thái ngừng hoạt động.");
        }

        $room->delete();

        return redirect()->route('admin.rooms.index')->with('success', "Đã xóa {$room->name}.");
    }
}
