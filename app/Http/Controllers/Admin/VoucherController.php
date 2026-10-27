<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\VoucherRequest;
use App\Models\Voucher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VoucherController extends Controller
{
    public function index(Request $request): View
    {
        $vouchers = Voucher::withCount(['confirmedBookings as used_count'])
            ->withSum('confirmedBookings as discount_total', 'discount')
            ->search($request->query('q'))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.vouchers.index', compact('vouchers'));
    }

    public function create(): View
    {
        return view('admin.vouchers.create', ['voucher' => new Voucher(['type' => Voucher::TYPE_PERCENT, 'is_active' => true])]);
    }

    public function store(VoucherRequest $request): RedirectResponse
    {
        $voucher = Voucher::create($request->voucherData());

        return redirect()->route('admin.vouchers.index')->with('success', "Đã tạo mã giảm giá {$voucher->code}.");
    }

    public function edit(Voucher $voucher): View
    {
        return view('admin.vouchers.edit', compact('voucher'));
    }

    public function update(VoucherRequest $request, Voucher $voucher): RedirectResponse
    {
        $voucher->update($request->voucherData());

        return redirect()->route('admin.vouchers.index')->with('success', "Đã cập nhật mã {$voucher->code}.");
    }

    public function destroy(Voucher $voucher): RedirectResponse
    {
        // Giữ lịch sử đơn hàng: mã đã có người dùng thì chỉ cho tạm khóa
        if ($voucher->bookings()->exists()) {
            return back()->with('error', "Mã {$voucher->code} đã được sử dụng nên không thể xóa. Hãy sửa và tắt \"Đang áp dụng\" để khóa mã.");
        }

        $voucher->delete();

        return redirect()->route('admin.vouchers.index')->with('success', "Đã xóa mã {$voucher->code}.");
    }
}
