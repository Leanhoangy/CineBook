<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MovieController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\ScheduleController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Khu vực công khai (khách chưa đăng nhập cũng xem được)
|--------------------------------------------------------------------------
*/
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/phim', [MovieController::class, 'index'])->name('movies.index');
Route::get('/phim/goi-y', [MovieController::class, 'suggest'])->name('movies.suggest'); // AJAX
Route::get('/phim/{movie}', [MovieController::class, 'show'])->name('movies.show');
Route::get('/lich-chieu', [ScheduleController::class, 'index'])->name('schedule.index');
Route::get('/uu-dai', [PageController::class, 'promotions'])->name('promotions');
Route::get('/ho-tro', [PageController::class, 'support'])->name('support');
Route::get('/suat-chieu/{showtime}/ghe-da-dat', [BookingController::class, 'seatStatus'])->name('showtimes.seats'); // AJAX

// VNPay gọi về sau khi thanh toán (có chữ ký nên không cần đăng nhập; IPN do server VNPay gọi)
Route::get('/thanh-toan/vnpay/ket-qua', [PaymentController::class, 'vnpayReturn'])->name('payments.vnpay.return');
Route::get('/thanh-toan/vnpay/ipn', [PaymentController::class, 'vnpayIpn'])->name('payments.vnpay.ipn');

/*
|--------------------------------------------------------------------------
| Đăng nhập / đăng ký (chỉ dành cho khách chưa đăng nhập)
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/dang-nhap', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/dang-nhap', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::get('/dang-ky', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/dang-ky', [AuthController::class, 'register']);

    Route::get('/quen-mat-khau', [PasswordResetController::class, 'showForgot'])->name('password.request');
    Route::post('/quen-mat-khau', [PasswordResetController::class, 'sendLink'])->name('password.email')->middleware('throttle:5,1');
    Route::get('/dat-lai-mat-khau/{token}', [PasswordResetController::class, 'showReset'])->name('password.reset');
    Route::post('/dat-lai-mat-khau', [PasswordResetController::class, 'reset'])->name('password.update');
});

/*
|--------------------------------------------------------------------------
| Khu vực người dùng đã đăng nhập
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::post('/dang-xuat', [AuthController::class, 'logout'])->name('logout');

    Route::get('/tai-khoan', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/tai-khoan', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/tai-khoan/mat-khau', [ProfileController::class, 'updatePassword'])->name('profile.password');

    Route::middleware('role:user')->group(function () {
        Route::get('/suat-chieu/{showtime}/dat-ve', [BookingController::class, 'create'])->name('bookings.create');
        Route::post('/suat-chieu/{showtime}/dat-ve', [BookingController::class, 'store'])->name('bookings.store');
        Route::get('/suat-chieu/{showtime}/ma-giam-gia', [BookingController::class, 'checkVoucher'])->name('bookings.voucher')->middleware('throttle:20,1'); // AJAX
        Route::get('/ve-cua-toi', [BookingController::class, 'index'])->name('bookings.index');
        Route::get('/ve-cua-toi/{booking:code}', [BookingController::class, 'show'])->name('bookings.show');
        Route::patch('/ve-cua-toi/{booking:code}/huy', [BookingController::class, 'cancel'])->name('bookings.cancel');
        Route::post('/ve-cua-toi/{booking:code}/thanh-toan', [PaymentController::class, 'pay'])->name('bookings.pay');

        Route::post('/phim/{movie}/danh-gia', [ReviewController::class, 'store'])->name('reviews.store');
        Route::delete('/danh-gia/{review}', [ReviewController::class, 'destroy'])->name('reviews.destroy');
    });
});

/*
|--------------------------------------------------------------------------
| Khu vực quản trị /admin (chỉ Admin)
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');

    Route::resource('genres', Admin\GenreController::class)->except('show');

    Route::get('movies/thung-rac', [Admin\MovieController::class, 'trash'])->name('movies.trash');
    Route::patch('movies/{movie}/khoi-phuc', [Admin\MovieController::class, 'restore'])->name('movies.restore')->withTrashed();
    Route::delete('movies/{movie}/xoa-vinh-vien', [Admin\MovieController::class, 'forceDelete'])->name('movies.force-delete')->withTrashed();
    Route::resource('movies', Admin\MovieController::class);

    Route::resource('rooms', Admin\RoomController::class)->except('show');
    Route::post('showtimes/{showtime}/huy', [Admin\ShowtimeController::class, 'cancel'])->name('showtimes.cancel');
    Route::resource('showtimes', Admin\ShowtimeController::class)->except('show');

    Route::get('soat-ve', [Admin\CheckInController::class, 'index'])->name('check-in.index');
    Route::get('soat-ve/suat-chieu', [Admin\CheckInController::class, 'showtimes'])->name('check-in.showtimes'); // AJAX
    Route::get('soat-ve/tra-cuu', [Admin\CheckInController::class, 'lookup'])->name('check-in.lookup'); // AJAX
    Route::post('soat-ve/{booking}', [Admin\CheckInController::class, 'store'])->name('check-in.store'); // AJAX

    Route::get('bookings/xuat-excel', [Admin\BookingController::class, 'export'])->name('bookings.export');
    Route::patch('bookings/{booking}/thu-tien', [Admin\BookingController::class, 'markPaid'])->name('bookings.mark-paid');
    Route::post('bookings/{booking}/hoan-tien', [Admin\BookingController::class, 'refund'])->name('bookings.refund');
    Route::resource('bookings', Admin\BookingController::class)->only(['index', 'show', 'update', 'destroy']);

    Route::resource('reviews', Admin\ReviewController::class)->only(['index', 'update', 'destroy']);
    Route::resource('vouchers', Admin\VoucherController::class)->except('show');
    Route::resource('combos', Admin\ComboController::class)->except('show');

    Route::resource('users', Admin\UserController::class)->except('show');
});
