# Phân công nhóm CineBook

> Mỗi người phụ trách **một mảng trọn vẹn** (trang khách + trang admin + CSDL liên quan) để khi thầy hỏi bất kỳ chỗ nào trong mảng đó đều trả lời được.
> Điền tên + MSSV vào bảng. Mỗi người nên **tự commit/sửa nhỏ phần của mình** lên GitHub (thêm comment, chỉnh giao diện…) để có lịch sử "tham gia thực hiện".

| | Thành viên | MSSV | Mảng phụ trách |
|---|---|---|---|
| TV1 | *(nhóm trưởng)* | | CSDL, dữ liệu phim thật, lịch chiếu tự động, thống kê & xuất Excel |
| TV2 | | | Tài khoản & phân quyền, mã giảm giá, bắp nước, trang Ưu đãi / Hỗ trợ |
| TV3 | | | Phim & lịch chiếu (khách + admin), tìm kiếm, đánh giá phim |
| TV4 | | | Đặt vé, thanh toán VNPay, hoàn tiền, vé QR, soát vé, quản lý đơn |

**Cách đọc code nhanh:** mở [`routes/web.php`](../routes/web.php) → tìm URL của trang → xem route gọi Controller nào, hàm nào → trong hàm đó xem dùng Model / View nào. Mọi trang đều đi theo đường **Route → Controller → Model → View (Blade)**.

---

## TV1 – CSDL, dữ liệu, tự động hóa, thống kê

### Trang phụ trách
| Trang | URL |
|---|---|
| Tổng quan (doanh thu tháng, vé hôm nay, biểu đồ 14 ngày, top phim) | `/admin` |
| Thống kê theo khoảng ngày (lấp đầy, khung giờ, top phim, bắp nước) | `/admin/thong-ke` |
| Xuất Excel đơn đặt vé | nút "Xuất Excel" ở `/admin/bookings` |

### File code
| Việc | File |
|---|---|
| Tạo bảng (15 migration nghiệp vụ, 12 bảng) | [`database/migrations/`](../database/migrations/) |
| Model + quan hệ giữa các bảng | [`app/Models/`](../app/Models/) – `Movie`, `Genre`, `Room`, `Showtime`, `Booking`, `BookingSeat`, `User`… |
| Dữ liệu mẫu | [`database/seeders/`](../database/seeders/), [`database/factories/`](../database/factories/) |
| Cào phim thật từ Moveek | [`app/Console/Commands/CrawlMovies.php`](../app/Console/Commands/CrawlMovies.php), [`app/Services/MovieImporter.php`](../app/Services/MovieImporter.php) |
| Tự xếp lịch chiếu 7 ngày, tự chuyển "Đang chiếu" / "Ngừng chiếu" | [`app/Services/ShowtimeScheduler.php`](../app/Services/ShowtimeScheduler.php), [`app/Console/Commands/RefreshShowtimes.php`](../app/Console/Commands/RefreshShowtimes.php) |
| Lên lịch chạy định kỳ | [`routes/console.php`](../routes/console.php) |
| Dashboard | [`Admin/DashboardController.php`](../app/Http/Controllers/Admin/DashboardController.php), [`admin/dashboard.blade.php`](../resources/views/admin/dashboard.blade.php) |
| Thống kê | [`app/Services/SalesReport.php`](../app/Services/SalesReport.php), [`Admin/ReportController.php`](../app/Http/Controllers/Admin/ReportController.php), [`admin/reports/index.blade.php`](../resources/views/admin/reports/index.blade.php), component [`admin/meter`](../resources/views/components/admin/meter.blade.php) |
| Xuất Excel | [`app/Exports/BookingsExport.php`](../app/Exports/BookingsExport.php), hàm `export()` trong [`Admin/BookingController.php`](../app/Http/Controllers/Admin/BookingController.php) |
| Cấu hình nghiệp vụ (giá vé, phụ thu VIP, thời gian hủy…) | [`config/cinema.php`](../config/cinema.php) |
| Test | `tests/Feature/ShowtimeRefreshTest.php`, `BookingExportTest.php`, `ReportTest.php` |

### Thầy hay hỏi
- **Tỉ lệ lấp đầy tính thế nào?** → Ghế đã bán (vé "Đã xác nhận") chia tổng ghế (`số hàng × số ghế mỗi hàng` của phòng) của các suất đã chiếu trong khoảng ngày. `SalesReport` dùng subquery `GROUP BY showtime_id` cho số ghế, doanh thu, bắp nước rồi mới JOIN vào suất chiếu, để không bị đếm trùng tiền khi 1 đơn có nhiều ghế.
- **Thống kê theo ngày đặt hay ngày chiếu?** → Trang Thống kê tính theo **ngày chiếu** (doanh thu ghi nhận khi suất diễn ra, khớp với tỉ lệ lấp đầy); trang Tổng quan tính theo ngày đặt (bán được bao nhiêu hôm nay). Kết quả đã đối chiếu với câu SQL viết tay.
- **CSDL có những bảng nào, quan hệ ra sao?** → Xem ERD trong [`THIET_KE.md`](THIET_KE.md). Phim – Thể loại là **nhiều-nhiều** qua bảng trung gian `genre_movie` (`belongsToMany`); Phim → Suất chiếu → Đơn đặt vé → Ghế là các quan hệ **1-nhiều** (`hasMany` / `belongsTo`).
- **Sao không có bảng `seats`?** → Ghế sinh từ `total_rows × seats_per_row` của phòng (`Room::seatMap()`), admin đổi sơ đồ phòng không phải sửa hàng trăm dòng. Bảng `booking_seats` chỉ lưu ghế đã bán **kèm giá lúc mua**, để admin đổi giá sau này không làm sai đơn cũ.
- **Khóa ngoại xóa thế nào?** → `bookings → users/showtimes` dùng `restrictOnDelete`: không xóa được dữ liệu đã có giao dịch. `booking_seats` dùng `cascadeOnDelete`: xóa đơn thì xóa luôn ghế của đơn.
- **Dữ liệu phim lấy ở đâu?** → Lệnh `php artisan movies:crawl` dùng HTTP Client của Laravel đọc trang Moveek, lưu ra `database/seeders/data/movies.json` + poster. `MovieSeeder` đọc file JSON đó, nên seed không cần mạng.
- **Lịch chiếu tự động thế nào?** → `ShowtimeScheduler::fill()`: mỗi ngày, 10 phòng mở từ 9h đến 23h, xếp lần lượt từng phim vào phòng rảnh sớm nhất. Phim mới ra (≤ 14 ngày) được nhiều suất hơn. Giờ kết thúc = giờ bắt đầu + thời lượng + 15 phút dọn phòng, nên các suất không bao giờ trùng nhau.
- **Tác vụ định kỳ?** → `routes/console.php`: `showtimes:refresh` chạy lúc 00:05 hằng ngày, `bookings:release-unpaid` chạy mỗi phút, `movies:crawl` chạy thứ Hai hằng tuần. Trên máy cá nhân thì chạy `php artisan schedule:work`.
- **Doanh thu tính sao?** → Chỉ tính đơn có `status = confirmed`, cộng `total_price` (đã trừ giảm giá, đã gồm bắp nước).
- **Xuất Excel 17.000 đơn có bị tràn bộ nhớ không?** → Không. Code đọc theo lô 500 đơn (`lazyByIdDesc`) và ghi từng dòng bằng thư viện OpenSpout.

---

## TV2 – Tài khoản, phân quyền, khuyến mãi

### Trang phụ trách
| Trang | URL |
|---|---|
| Đăng nhập / Đăng ký / Quên mật khẩu / Đặt lại mật khẩu | `/dang-nhap`, `/dang-ky`, `/quen-mat-khau`, `/dat-lai-mat-khau/{token}` |
| Tài khoản (đổi thông tin, avatar, mật khẩu) | `/tai-khoan` |
| Ưu đãi (chương trình, mã giảm giá, bảng giá vé) · Hỗ trợ (FAQ) | `/uu-dai`, `/ho-tro` |
| Mã giảm giá & bắp nước ở bước chọn ghế | khối "Mã dành cho bạn", khối "Bắp nước" trên `/suat-chieu/{id}/dat-ve` |
| Admin: Người dùng (tạo cả tài khoản nhân viên soát vé) · Mã giảm giá · Bắp nước | `/admin/users`, `/admin/vouchers`, `/admin/combos` |
| Trang lỗi 403 / 404 / 419 / 500 | `resources/views/errors/` |

### File code
| Việc | File |
|---|---|
| Đăng nhập, đăng ký, đăng xuất | [`AuthController.php`](../app/Http/Controllers/AuthController.php), [`Requests/Auth/`](../app/Http/Requests/Auth/), [`views/auth/`](../resources/views/auth/) |
| Quên / đặt lại mật khẩu (gửi email) | [`PasswordResetController.php`](../app/Http/Controllers/PasswordResetController.php), [`Notifications/ResetPasswordNotification.php`](../app/Notifications/ResetPasswordNotification.php) |
| Phân quyền admin / nhân viên soát vé / khách | [`Middleware/EnsureUserHasRole.php`](../app/Http/Middleware/EnsureUserHasRole.php) (đăng ký tên `role` trong [`bootstrap/app.php`](../bootstrap/app.php)), nhóm route `role:admin`, `role:admin,staff` (trang Soát vé), `role:user` trong `routes/web.php`; `User::homeUrl()` chọn trang sau khi đăng nhập; menu admin lọc theo vai trò trong [`layouts/admin.blade.php`](../resources/views/layouts/admin.blade.php) |
| Hồ sơ + upload avatar | [`ProfileController.php`](../app/Http/Controllers/ProfileController.php), [`ProfileRequest.php`](../app/Http/Requests/ProfileRequest.php), [`profile/edit.blade.php`](../resources/views/profile/edit.blade.php) |
| Quản lý người dùng | [`Admin/UserController.php`](../app/Http/Controllers/Admin/UserController.php), [`Requests/Admin/UserRequest.php`](../app/Http/Requests/Admin/UserRequest.php) |
| Mã giảm giá: điều kiện, tính tiền giảm | [`Models/Voucher.php`](../app/Models/Voucher.php) (`discountFor`, `unavailableReason`, `usableBy`), [`Admin/VoucherController.php`](../app/Http/Controllers/Admin/VoucherController.php), hàm `checkVoucher()` trong `BookingController` (AJAX) |
| Bắp nước | [`Models/Combo.php`](../app/Models/Combo.php), [`Models/BookingCombo.php`](../app/Models/BookingCombo.php), [`Admin/ComboController.php`](../app/Http/Controllers/Admin/ComboController.php), [`views/admin/combos/`](../resources/views/admin/combos/) |
| JS mã giảm giá + bắp nước trên trang chọn ghế | phần `voucher` / `combos` trong [`public/js/seat-picker.js`](../public/js/seat-picker.js) |
| Trang Ưu đãi, Hỗ trợ | [`PageController.php`](../app/Http/Controllers/PageController.php), [`views/pages/`](../resources/views/pages/), [`components/voucher-ticket.blade.php`](../resources/views/components/voucher-ticket.blade.php) |
| Test | `AuthAndRoleTest.php`, `PasswordResetAndMailTest.php`, `VoucherTest.php`, `ComboTest.php`, `PromotionTest.php` |

### Thầy hay hỏi
- **Mật khẩu lưu thế nào?** → Model `User` khai báo cast `'password' => 'hashed'`, Laravel tự băm bằng bcrypt trước khi lưu. Trong CSDL không có mật khẩu dạng chữ thường.
- **Có mấy vai trò?** → 3: `admin` (toàn quyền), `staff` (nhân viên soát vé: đăng nhập vào thẳng trang Soát vé, menu chỉ có mục đó, các trang khác 403, không xem được chi tiết đơn) và `user` (khách). Cột `role` không nằm trong `$fillable` nên khách không tự nâng quyền qua form được; chỉ admin đổi được vai trò ở trang Người dùng.
- **Khách vào `/admin` thì sao?** → Middleware `role:admin` kiểm tra `$user->role`; sai vai trò thì trả **403**, chưa đăng nhập thì chuyển về trang đăng nhập (middleware `auth`).
- **Chống dò mật khẩu?** → Route đăng nhập có `throttle:10,1` (tối đa 10 lần/phút). Đăng nhập xong gọi `session()->regenerate()` để chống chiếm phiên.
- **Quên mật khẩu chạy thế nào?** → Dùng Password Broker của Laravel: tạo token lưu bảng `password_reset_tokens` và gửi link qua email. Link có hiệu lực 60 phút, dùng 1 lần.
- **Upload avatar kiểm tra gì?** → `ProfileRequest`: `image`, `mimes:jpg,jpeg,png,webp`, `max:2048` KB. File lưu trong `storage/app/public/avatars`, cần chạy `php artisan storage:link` để hiển thị.
- **Mã giảm giá được kiểm tra những gì?** → `Voucher::unavailableReason()`: mã đang bật, trong thời hạn, còn lượt (chỉ đếm vé chưa hủy), khách chưa dùng mã này lần nào, đơn đủ giá trị tối thiểu. Mã giảm % có mức giảm tối đa.
- **2 người dùng mã cuối cùng cùng lúc thì sao?** → Lúc đặt vé, dòng mã giảm giá được **khóa** (`lockForUpdate`) trong transaction, nên đơn thứ hai phải đợi và bị báo hết lượt.
- **Khách sửa giá bắp nước bằng F12 được không?** → Không. Trình duyệt chỉ gửi `combos[id] = số lượng`, server tự lấy giá trong CSDL và lưu lại giá lúc mua vào `booking_combos`.

---

## TV3 – Phim, lịch chiếu, tìm kiếm, đánh giá

### Trang phụ trách
| Trang | URL |
|---|---|
| Trang chủ (phim nổi bật, đang chiếu, sắp chiếu, ưu đãi) | `/` |
| Danh sách phim: tìm kiếm, lọc thể loại/trạng thái, phân trang | `/phim` |
| Gợi ý tìm kiếm khi gõ (AJAX) | ô tìm kiếm trên thanh menu |
| Chi tiết phim: trailer, lịch chiếu, đánh giá, "Mở bán vé từ…" | `/phim/{slug}` |
| Lịch chiếu theo ngày | `/lich-chieu` |
| Admin: Phim (thùng rác) · Thể loại · Phòng chiếu · Suất chiếu · Đánh giá | `/admin/movies`, `/admin/genres`, `/admin/rooms`, `/admin/showtimes`, `/admin/reviews` |

### File code
| Việc | File |
|---|---|
| Trang chủ | [`HomeController.php`](../app/Http/Controllers/HomeController.php), [`home.blade.php`](../resources/views/home.blade.php) |
| Danh sách, chi tiết phim, gợi ý tìm kiếm | [`MovieController.php`](../app/Http/Controllers/MovieController.php) (`index`, `show`, `suggest`), [`views/movies/`](../resources/views/movies/), [`public/js/live-search.js`](../public/js/live-search.js) |
| Tìm kiếm / lọc trong Model | `scopeSearch`, `scopeOfGenre`, `scopeOfStatus`, `presaleOpensOn` trong [`Models/Movie.php`](../app/Models/Movie.php) |
| Lịch chiếu | [`ScheduleController.php`](../app/Http/Controllers/ScheduleController.php), [`schedule/index.blade.php`](../resources/views/schedule/index.blade.php) |
| CRUD phim, upload poster, xóa mềm | [`Admin/MovieController.php`](../app/Http/Controllers/Admin/MovieController.php), [`Requests/Admin/MovieRequest.php`](../app/Http/Requests/Admin/MovieRequest.php), [`views/admin/movies/`](../resources/views/admin/movies/) |
| Thể loại, phòng chiếu | [`Admin/GenreController.php`](../app/Http/Controllers/Admin/GenreController.php), [`Admin/RoomController.php`](../app/Http/Controllers/Admin/RoomController.php), [`Models/Room.php`](../app/Models/Room.php) (`seatMap`, `isVipRow`) |
| Suất chiếu, chặn trùng giờ | [`Admin/ShowtimeController.php`](../app/Http/Controllers/Admin/ShowtimeController.php), hàm `after()` trong [`Requests/Admin/ShowtimeRequest.php`](../app/Http/Requests/Admin/ShowtimeRequest.php) |
| Đánh giá phim | [`ReviewController.php`](../app/Http/Controllers/ReviewController.php), [`Policies/ReviewPolicy.php`](../app/Policies/ReviewPolicy.php), `canBeReviewedBy()` trong `Movie.php`, [`Admin/ReviewController.php`](../app/Http/Controllers/Admin/ReviewController.php) |
| Thẻ phim, poster dùng chung | [`components/movie-card.blade.php`](../resources/views/components/movie-card.blade.php), [`components/poster.blade.php`](../resources/views/components/poster.blade.php) |
| Test | `MovieSearchTest.php`, `AdminCrudTest.php`, `ReviewTest.php` |

### Thầy hay hỏi
- **Tìm kiếm + phân trang mà vẫn giữ từ khóa?** → `Movie::search($q)->ofGenre(...)->ofStatus(...)->paginate(8)->withQueryString()`; hàm `withQueryString()` gắn `?q=...` vào link trang 2, 3…
- **Gợi ý khi gõ làm sao?** → `live-search.js` đợi người dùng ngừng gõ 250ms (debounce) rồi gọi `fetch('/phim/goi-y?q=...')`; server (`MovieController::suggest`) trả JSON tối đa vài phim; JS vẽ danh sách, dùng phím ↑↓ để chọn.
- **Xóa mềm là gì?** → Model `Movie` dùng trait `SoftDeletes`: xóa chỉ ghi `deleted_at`, phim vào **Thùng rác** và khôi phục được. Vé cũ vẫn hiện được tên phim. Chỉ xóa vĩnh viễn được khi phim chưa từng có suất chiếu.
- **Chặn 2 suất trùng giờ trong 1 phòng?** → `ShowtimeRequest::after()` tìm suất cùng phòng có `start_time < giờ kết thúc mới` **và** `end_time > giờ bắt đầu mới`; có kết quả là báo lỗi. Suất đã có người đặt vé thì không cho sửa hoặc xóa.
- **Validation báo lỗi tiếng Việt thế nào?** → Dùng Form Request (`MovieRequest`…); thông báo lỗi lấy từ [`lang/vi/validation.php`](../lang/vi/validation.php), hiển thị bằng `@error` trong Blade.
- **Chống đánh giá ảo?** → `Movie::canBeReviewedBy()`: chỉ khách có vé **đã xác nhận** của suất **đã chiếu** mới đánh giá được; mỗi phim 1 đánh giá. `ReviewPolicy` chỉ cho xóa đánh giá của chính mình. Admin có thể ẩn bình luận vi phạm.
- **Phim sắp chiếu đặt vé trước thế nào?** → Lịch chiếu xếp sẵn 7 ngày, nên phim khởi chiếu trong 7 ngày tới đã có suất và có nhãn "Đặt vé trước". Phim còn xa ngày chiếu hiện "Mở bán vé từ …" (`presaleOpensOn()`).

---

## TV4 – Đặt vé, thanh toán, hoàn tiền, vé QR, soát vé

### Trang phụ trách
| Trang | URL |
|---|---|
| Chọn ghế (sơ đồ ghế tự cập nhật) | `/suat-chieu/{id}/dat-ve` |
| Vé của tôi · Vé điện tử (QR, phóng to QR, hủy vé, thanh toán) | `/ve-cua-toi`, `/ve-cua-toi/{mã vé}` |
| Thanh toán VNPay (đi và về) | nút "Thanh toán qua VNPay", `/thanh-toan/vnpay/ket-qua`, `/thanh-toan/vnpay/ipn` |
| Ô nhắc "Vé hôm nay" ở trang chủ | đầu trang `/` khi có vé trong ngày |
| Admin: Soát vé (quét QR) · Đơn đặt vé | `/admin/soat-ve`, `/admin/bookings` |
| Admin: Hoàn tiền · Hủy suất chiếu (sự cố) | ô "Hủy vé & hoàn tiền" ở `/admin/bookings/{id}`, lọc "Chờ hoàn tiền"; nút ⊗ ở `/admin/showtimes` |
| Email vé điện tử, email báo vé tự hủy | `resources/views/emails/` |

### File code
| Việc | File |
|---|---|
| Chọn ghế, lưu đơn, hủy vé, vé của tôi | [`BookingController.php`](../app/Http/Controllers/BookingController.php) (`create`, `store`, `show`, `cancel`, `seatStatus`), [`StoreBookingRequest.php`](../app/Http/Requests/StoreBookingRequest.php), [`views/bookings/`](../resources/views/bookings/) |
| Sơ đồ ghế, tính tiền, cập nhật mỗi 10 giây | [`public/js/seat-picker.js`](../public/js/seat-picker.js) |
| Quy tắc của vé (hủy, thanh toán, hạn thanh toán, soát vé) | [`Models/Booking.php`](../app/Models/Booking.php), [`Policies/BookingPolicy.php`](../app/Policies/BookingPolicy.php), `bookedSeatCodes()` / `isBookable()` trong [`Models/Showtime.php`](../app/Models/Showtime.php) |
| VNPay | [`app/Services/VnpayGateway.php`](../app/Services/VnpayGateway.php), [`PaymentController.php`](../app/Http/Controllers/PaymentController.php), cấu hình `vnpay` trong [`config/services.php`](../config/services.php) |
| Mã QR vé | [`app/Services/TicketQr.php`](../app/Services/TicketQr.php) (thư viện `chillerlan/php-qrcode`) |
| Tự hủy vé chưa thanh toán | [`app/Services/UnpaidBookingReleaser.php`](../app/Services/UnpaidBookingReleaser.php), [`Console/Commands/ReleaseUnpaidBookings.php`](../app/Console/Commands/ReleaseUnpaidBookings.php) |
| Soát vé (quét QR, bảng suất đang soát) | [`Admin/CheckInController.php`](../app/Http/Controllers/Admin/CheckInController.php) (`index`, `showtimes`, `lookup`, `store`), [`views/admin/check-in/`](../resources/views/admin/check-in/), [`public/js/check-in.js`](../public/js/check-in.js) |
| Quản lý đơn (lọc, xác nhận/hủy, thu tiền tại quầy) | [`Admin/BookingController.php`](../app/Http/Controllers/Admin/BookingController.php), [`views/admin/bookings/`](../resources/views/admin/bookings/) |
| Hoàn tiền, hủy suất chiếu | [`app/Services/BookingRefunder.php`](../app/Services/BookingRefunder.php), [`app/Services/ShowtimeCanceller.php`](../app/Services/ShowtimeCanceller.php), `refund()` trong [`VnpayGateway.php`](../app/Services/VnpayGateway.php), `refund()` trong [`Admin/BookingController.php`](../app/Http/Controllers/Admin/BookingController.php), `cancel()` trong [`Admin/ShowtimeController.php`](../app/Http/Controllers/Admin/ShowtimeController.php) |
| Email | [`Mail/BookingConfirmed.php`](../app/Mail/BookingConfirmed.php), [`Mail/BookingReleased.php`](../app/Mail/BookingReleased.php), [`Mail/BookingCancelledByCinema.php`](../app/Mail/BookingCancelledByCinema.php), [`views/emails/`](../resources/views/emails/) |
| Test | `BookingTest.php`, `PaymentTest.php`, `RefundTest.php`, `CheckInTest.php`, `UnpaidReleaseTest.php` |

### Thầy hay hỏi
- **2 người bấm đặt cùng 1 ghế cùng lúc thì sao?** → `BookingController::store()` chạy trong `DB::transaction` và **khóa dòng suất chiếu** (`lockForUpdate`). Người thứ hai phải đợi người đầu lưu xong, sau đó kiểm tra lại `bookedSeatCodes()` và bị báo "Ghế … vừa có người đặt".
- **Sơ đồ ghế tự cập nhật thế nào?** → `seat-picker.js` gọi `fetch` tới `/suat-chieu/{id}/ghe-da-dat` mỗi 10 giây (AJAX, nhận JSON). Ghế vừa bị người khác mua thì bị khóa lại; nếu khách đang chọn ghế đó thì bỏ chọn và báo cho khách.
- **Tiền tính ở trình duyệt có tin được không?** → Không. Trình duyệt chỉ tính để hiển thị; khi lưu, server tính lại giá ghế (`seatPrice`), giảm giá và bắp nước từ CSDL.
- **VNPay bảo mật thế nào?** → Link thanh toán được ký **HMAC-SHA512** bằng chuỗi bí mật (`VNPAY_HASH_SECRET` trong `.env`, không đưa lên GitHub). Kết quả trả về phải **đúng chữ ký** và **đúng số tiền** mới được ghi nhận. Kết quả có thể về 2 lần (trình duyệt quay về + IPN từ server VNPay), nhưng vé đã bị khóa dòng nên chỉ được ghi nhận 1 lần.
- **Đặt rồi không trả tiền thì sao?** → Hạn thanh toán là trước giờ chiếu 30 phút (`paymentDeadline()`). Lệnh `bookings:release-unpaid` chạy mỗi phút, tự hủy vé quá hạn, mở bán lại ghế và gửi email báo khách.
- **QR trên vé chứa gì, tạo thế nào?** → QR chỉ chứa **mã vé**, được tạo ngay trên server bằng thư viện PHP (`TicketQr`), không gọi dịch vụ ngoài. Trong email, QR được nhúng dạng ảnh PNG đính kèm vì Gmail chặn ảnh SVG.
- **Nhân viên biết suất nào tới giờ soát vé bằng cách nào?** → Trang Soát vé có bảng "Suất đang soát vé" (`Showtime::scopeCheckInOpen()`: từ 60 phút trước giờ chiếu tới khi phim kết thúc) và "Sắp mở soát" trong 2 giờ tới, kèm số vé đã vào/đã bán. Trình duyệt gọi AJAX `/admin/soat-ve/suat-chieu` mỗi 30 giây, thấy suất mới vào giờ soát thì hiện thông báo + tiếng bíp. Menu admin hiện số suất đang soát (View Composer trong `AppServiceProvider`).
- **Soát vé chống dùng 1 vé 2 lần?** → Khi cho vào, server khóa dòng vé rồi ghi `checked_in_at`. Lần quét sau, `Booking::checkInProblem()` báo "Vé đã được soát lúc …". Vé đã hủy, chưa tới giờ (mở soát trước giờ chiếu 60 phút) hoặc suất đã kết thúc cũng bị chặn.
- **Sao khách không xem được vé của người khác?** → `BookingPolicy::view()`: chỉ chủ vé hoặc admin; route dùng mã vé ngẫu nhiên (`CBxxxxxx`) chứ không dùng id tăng dần.
- **Khách đã trả tiền online muốn hủy thì sao?** → Giống các rạp thật ở Việt Nam: vé đã thanh toán **không đổi trả khi khách đổi ý** (`canBeCancelledByCustomer()` chặn). Rạp chỉ hoàn tiền khi lỗi từ phía rạp: admin bấm **"Hủy vé & hoàn tiền"**.
- **Hoàn tiền làm thế nào?** → Vé trả qua VNPay: `VnpayGateway::refund()` gọi **API hoàn tiền của VNPay** (`vnp_Command=refund`, gửi mã giao dịch gốc `vnp_TxnRef`, `vnp_TransactionNo`, ngày thanh toán, số tiền; ký HMAC-SHA512 các trường nối bằng `|`). Tiền về đúng thẻ khách đã dùng. Vé trả tại quầy (hoặc VNPay lỗi): nhân viên trả tiền rồi bấm "Đã trả lại", bắt buộc ghi cách trả. Hệ thống lưu `refunded_at`, `refund_method`, `refund_ref` và email báo khách. Vé được khóa dòng nên không hoàn 2 lần; vé đã hoàn không khôi phục hay xóa được.
- **Sandbox VNPay hoàn tiền được không?** → VNPay **khóa API hoàn tiền trên sandbox** (trả mã 99), phải liên hệ VNPay mới mở. Web báo rõ lỗi và mở sẵn ô hoàn thủ công; phần gọi API được kiểm chứng bằng test (`RefundTest`, giả lập phản hồi VNPay bằng `Http::fake`).
- **Rạp hủy cả suất chiếu?** → Nút ⊗ ở trang Suất chiếu → `ShowtimeCanceller`: hủy mọi vé (`cancel_reason = showtime`), vé VNPay hoàn tự động, vé còn lại chuyển "Chờ hoàn tiền", email từng khách, rồi **xóa mềm** suất chiếu (`SoftDeletes`) để nó tự biến mất khỏi mọi trang khách, còn vé cũ vẫn xem được.
- **Khách trả tiền đúng lúc vé vừa tự hủy?** → `VnpayGateway::process()` vẫn ghi nhận số tiền đã nhận, vé chuyển "Chờ hoàn tiền", Dashboard và trang Đơn đặt vé hiện cảnh báo cho admin.
- **Gửi email lỗi thì sao?** → Gửi email nằm trong `try/catch`: lỗi SMTP chỉ được ghi log, việc đặt vé vẫn thành công.

---

## Câu hỏi chung (ai cũng nên trả lời được)

- **Mô hình MVC trong Laravel?** → Route (`routes/web.php`) nhận URL → **Controller** xử lý → **Model** (Eloquent) đọc/ghi CSDL → trả **View** (Blade, `resources/views/`). Ví dụ: `/phim` → `MovieController@index` → `Movie::search()` → `movies/index.blade.php`.
- **Layout dùng chung?** → `layouts/app.blade.php` (trang khách) và `layouts/admin.blade.php` (trang quản trị); trang con dùng `@extends` + `@section`. Phần lặp lại dùng component `<x-movie-card>`, `<x-poster>`…
- **Thông báo sau thêm/sửa/xóa?** → Controller `redirect()->with('success', '...')` (flash session) → `partials/flash.blade.php` hiện toast Bootstrap.
- **CSRF là gì?** → Mỗi form có `@csrf`; AJAX gửi header `X-CSRF-TOKEN` lấy từ thẻ `<meta name="csrf-token">`. Không có token thì Laravel trả lỗi 419.
- **Chống SQL Injection?** → Eloquent / Query Builder dùng tham số ràng buộc (prepared statement); code không nối chuỗi SQL từ dữ liệu người dùng.
- **Chạy test?** → `php artisan test`: 97 test chạy trên SQLite trong bộ nhớ, không đụng dữ liệu thật.

## Cách demo gợi ý (mỗi người trình bày phần mình)

1. **TV3**: trang chủ → tìm phim (gợi ý khi gõ) → chi tiết phim → lịch chiếu; vào admin thêm 1 suất chiếu trùng giờ để thấy báo lỗi.
2. **TV4**: chọn ghế (mở 2 trình duyệt để thấy ghế tự khóa) → đặt vé → thanh toán VNPay thẻ test NCB → vé "Đã thanh toán" → admin quét QR ở trang Soát vé → mở 1 đơn "Chờ hoàn tiền" và hoàn tiền.
3. **TV2**: đăng ký / quên mật khẩu → áp mã giảm giá + chọn bắp nước ở bước chọn ghế → admin tạo mã giảm giá / combo mới; thử vào `/admin` bằng tài khoản khách để thấy lỗi 403; đăng nhập `soatve@cinebook.test` để thấy nhân viên chỉ vào được trang Soát vé.
4. **TV1**: ERD → chạy `php artisan showtimes:refresh` / `movies:crawl` → Dashboard doanh thu → trang Thống kê (đổi khoảng ngày, xem khung giờ nào lấp đầy cao) → xuất Excel.
