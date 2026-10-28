<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ComboRequest;
use App\Models\Booking;
use App\Models\Combo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/** Quản lý bắp nước bán kèm vé */
class ComboController extends Controller
{
    public function index(): View
    {
        // Số phần đã bán và doanh thu chỉ tính đơn còn hiệu lực
        $sold = fn ($q) => $q->whereHas('booking', fn ($b) => $b->where('status', Booking::STATUS_CONFIRMED));

        $combos = Combo::withSum(['bookingCombos as sold' => $sold], 'quantity')
            ->withSum(['bookingCombos as revenue' => $sold], DB::raw('price * quantity'))
            ->orderByDesc('is_active')
            ->orderBy('id')
            ->get();

        return view('admin.combos.index', compact('combos'));
    }

    public function create(): View
    {
        return view('admin.combos.create', ['combo' => new Combo(['is_active' => true])]);
    }

    public function store(ComboRequest $request): RedirectResponse
    {
        $data = $request->safe()->except(['image', 'remove_image']);
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('combos', 'public');
        }

        $combo = Combo::create($data);

        return redirect()->route('admin.combos.index')->with('success', "Đã thêm {$combo->name}.");
    }

    public function edit(Combo $combo): View
    {
        return view('admin.combos.edit', compact('combo'));
    }

    public function update(ComboRequest $request, Combo $combo): RedirectResponse
    {
        $data = $request->safe()->except(['image', 'remove_image']);

        if ($request->hasFile('image') || $request->boolean('remove_image')) {
            if ($combo->image) {
                Storage::disk('public')->delete($combo->image);
            }
            $data['image'] = $request->hasFile('image') ? $request->file('image')->store('combos', 'public') : null;
        }

        $combo->update($data);

        return redirect()->route('admin.combos.index')->with('success', "Đã cập nhật {$combo->name}.");
    }

    public function destroy(Combo $combo): RedirectResponse
    {
        // Giữ lịch sử đơn hàng: combo đã bán thì chỉ cho ngừng bán
        if ($combo->bookingCombos()->exists()) {
            return back()->with('error', "{$combo->name} đã có khách mua nên không thể xóa. Hãy sửa và tắt \"Đang bán\" để ngừng bán.");
        }

        if ($combo->image) {
            Storage::disk('public')->delete($combo->image);
        }
        $combo->delete();

        return redirect()->route('admin.combos.index')->with('success', "Đã xóa {$combo->name}.");
    }
}
