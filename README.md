# 🎬 CineBook – Website đặt vé xem phim

Đồ án cuối môn **Lập trình mã nguồn mở** – xây dựng bằng **Laravel 13 + MySQL + Blade/Bootstrap 5**.

CineBook cho phép khách hàng xem phim đang chiếu, tra lịch chiếu, **chọn ghế trực tiếp trên sơ đồ phòng chiếu**, mua kèm bắp nước, đặt vé và thanh toán online (VNPay); nhân viên **soát vé bằng mã QR** tại cửa phòng chiếu; quản trị viên quản lý phim, thể loại, phòng chiếu, suất chiếu, đơn đặt vé (thu tiền, hoàn tiền), mã giảm giá, bắp nước, người dùng và theo dõi doanh thu, thống kê.

---

## 1. Yêu cầu môi trường

| Phần mềm | Phiên bản |
|---|---|
| PHP | ≥ 8.3 (bật extension `pdo_mysql`, `mbstring`, `fileinfo`, `gd`, `zip`) |
| Composer | 2.x |
| MySQL / MariaDB | MySQL 8+ hoặc MariaDB 10.6+ (XAMPP, Laragon, Herd đều dùng được) |

> Giao diện dùng Bootstrap 5 qua CDN nên **không cần cài Node.js/npm**. Máy cần có Internet để tải CSS/JS từ CDN.

## 2. Cài đặt và chạy dự án

> Chạy **từng lệnh một**. Các dòng bắt đầu bằng `#` là chú thích, không cần gõ.

```bash
# 1. Cài thư viện PHP
composer install

# 2. Tạo file cấu hình
# (Windows: copy .env.example .env)
cp .env.example .env
php artisan key:generate

# 3. Tạo database tên "cinebook" (utf8mb4_unicode_ci) trong MySQL/phpMyAdmin
#    rồi kiểm tra DB_DATABASE, DB_USERNAME, DB_PASSWORD trong file .env

# 4. Tạo bảng + dữ liệu mẫu
php artisan migrate --seed

# 5. Tạo liên kết thư mục ảnh upload (poster, avatar)
php artisan storage:link

# 6. Chạy server
php artisan serve
```

Mở trình duyệt: **http://localhost:8000**

**Cách khác:** import trực tiếp file [`database/cinebook.sql`](database/cinebook.sql) (~12 MB, khoảng 40.000 đơn đặt vé mẫu trong 35 ngày gần nhất) vào database `cinebook` bằng phpMyAdmin thay cho bước 4; nếu phpMyAdmin báo file quá lớn thì dùng `migrate --seed` (mất khoảng 1 phút). Khi đó cần tự chép poster: copy các file trong `database/seeders/data/posters/` vào `storage/app/public/posters/` (nếu chạy `migrate --seed` thì seeder tự chép).

### Dữ liệu phim thật

Danh sách phim (tên, mô tả, đạo diễn, diễn viên, thời lượng, ngày khởi chiếu, phân loại tuổi, thể loại, trailer, poster) được cào từ [Moveek.com](https://moveek.com) – phim **đang chiếu**, **sắp chiếu** và một số phim **đã ngừng chiếu** gần đây tại Việt Nam – và lưu sẵn trong `database/seeders/data/` (`movies.json` + thư mục `posters/`), nên `migrate --seed` không cần Internet. Để cập nhật phim mới nhất:

```bash
# Mặc định: phim đang chiếu + 20 phim sắp chiếu + 15 phim đã ngừng chiếu
php artisan movies:crawl
# Lấy nhiều phim sắp chiếu hơn
php artisan movies:crawl --upcoming=40
# Nạp lại database (xóa dữ liệu cũ)
php artisan migrate:fresh --seed
# Chỉ lấy phim đang/sắp chiếu và thêm phim mới vào database, KHÔNG xóa dữ liệu cũ
php artisan movies:crawl --ended=0 --import
```

Trên server thật (`APP_ENV=production`), lệnh `movies:crawl --ended=0 --import` được lên lịch chạy **3h sáng thứ Hai hằng tuần** để danh sách phim sắp chiếu không bị cạn. Phim chưa có thì thêm mới, phim đã có thì giữ nguyên (chỉ gia hạn ngày kết thúc chiếu nếu Moveek vẫn còn chiếu).

> Lệnh cào nghỉ 2 giây giữa các request. Nếu Moveek tạm chặn (lỗi 403), lệnh sẽ dừng và **giữ nguyên dữ liệu cũ** – hãy thử lại sau vài giờ.

Phòng chiếu, suất chiếu (35 ngày trước đến 7 ngày sau ngày seed, đủ để xem thống kê trọn 1 tháng), người dùng và đơn đặt vé vẫn được sinh tự động bởi seeder, mô phỏng rạp thật: phim mới suất tối / cuối tuần gần kín, suất sáng phim cũ vắng; khách đi theo nhóm ngồi liền nhau, thích giữa và cuối rạp, 2 hàng sát màn hình chỉ có người khi suất gần kín. Moveek không công bố ngày kết thúc chiếu nên được ước tính 8 tuần sau khởi chiếu (phim chiếu lại có ngày khởi chiếu cũ thì chiếu thêm 2–4 tuần); admin sửa được trong form phim. Phim sắp chiếu chưa công bố thời lượng được tạm tính 120 phút; phim chưa có phân loại tuổi được để mặc định `P`. Trang khách chỉ hiện những thể loại còn phim đang/sắp chiếu.

### Lịch chiếu luôn cập nhật

Seeder chỉ tạo suất chiếu đến 7 ngày sau ngày seed. Để web luôn có suất chiếu (nhất là **trước buổi demo**), chạy:

```bash
# Cập nhật trạng thái phim + xếp suất chiếu 7 ngày tới
php artisan showtimes:refresh
```

Lệnh này làm 3 việc, giống cách rạp thật vận hành:
- Phim **đến ngày khởi chiếu** → "Đang chiếu"; phim **qua ngày kết thúc chiếu** → "Ngừng chiếu" (phim để trống ngày kết thúc thì admin tự đổi trạng thái).
- Phim **sắp chiếu** khởi chiếu trong 7 ngày tới được xếp suất từ ngày khởi chiếu → khách **đặt vé trước**.
- Không xếp suất cho phim sau ngày kết thúc chiếu.

Cách xếp lịch (`app/Services/ShowtimeScheduler.php`): 10 phòng chiếu từ 9h đến 23h, phim nào cũng có suất mỗi ngày; phim khởi chiếu trong 2 tuần được nhiều suất hơn (~3 suất/ngày rải sáng – chiều – tối), phim cũ 1–2 suất/ngày; các suất trong cùng phòng không bao giờ trùng giờ.

Lệnh này đã được lên lịch chạy tự động lúc 00:05 mỗi ngày; khi phát triển trên máy cá nhân, mở thêm một terminal chạy `php artisan schedule:work` để bộ lập lịch hoạt động.

### Tự hủy vé chưa thanh toán

Vé đặt xong giữ ghế ngay nhưng phải thanh toán (VNPay hoặc tại quầy) **trước giờ chiếu 30 phút**; khách đặt sát giờ vẫn có ít nhất 15 phút. Quá hạn, lệnh `php artisan bookings:release-unpaid` (lên lịch **chạy mỗi phút**) tự hủy vé, mở bán lại ghế và gửi email báo khách – tránh việc đặt giữ nhiều ghế đẹp rồi không đến. Hạn thanh toán hiện trên vé điện tử, email và ô nhắc vé ở trang chủ; link VNPay cũng hết hạn đúng lúc này. Cần chạy `php artisan schedule:work` khi demo trên máy cá nhân.

### Tài khoản demo (mật khẩu đều là `password`)

| Vai trò | Email | Đăng nhập xong sẽ vào |
|---|---|---|
| Quản trị viên | `admin@cinebook.test` | `/admin` |
| Nhân viên soát vé | `soatve@cinebook.test` | `/admin/soat-ve` (chỉ được dùng trang này) |
| Khách hàng | `user@cinebook.test` | Trang chủ |

**Mã giảm giá mẫu** (tài khoản demo chưa dùng mã nào): `CINEBOOK10` (giảm 10%, tối đa 30.000 ₫), `GIAM20K` (đơn từ 150.000 ₫), `SINHVIEN`, `NHOMBAN4` (đơn từ 300.000 ₫); `THANHVIENMOI` (20 khách đầu tiên) đã hết lượt, `HE2026` đã hết hạn và `GIANGSINH` chưa tới ngày để demo thông báo lỗi.

**Thanh toán online VNPay (sandbox, không mất tiền thật):** không cấu hình thì web chỉ nhận thanh toán tại quầy. Muốn demo thanh toán online:
1. Đăng ký tài khoản thử nghiệm tại [sandbox.vnpayment.vn/devreg](https://sandbox.vnpayment.vn/devreg/), VNPay gửi **mã website (TMN Code)** và **chuỗi bí mật (Hash Secret)** qua email.
2. Điền vào `.env`: `VNPAY_TMN_CODE=...` và `VNPAY_HASH_SECRET=...`
3. Đặt vé → trang vé bấm **"Thanh toán ... qua VNPay"** → chọn *Thẻ nội địa* → ngân hàng **NCB**, dùng thẻ test: số thẻ `9704198526191432198`, tên `NGUYEN VAN A`, ngày phát hành `07/15`, OTP `123456`.

**Hoàn tiền:** theo chính sách chung của các rạp, vé đã thanh toán không được hoàn khi khách đổi ý; rạp chỉ hoàn khi lỗi từ phía rạp (sự cố, hủy suất chiếu) hoặc khách trả tiền đúng lúc vé vừa tự hủy. Admin bấm "Hủy vé & hoàn tiền" ở chi tiết đơn: vé VNPay gọi API hoàn tiền của VNPay, vé trả tại quầy ghi nhận hoàn thủ công. Lưu ý: **VNPay khóa API hoàn tiền trên tài khoản sandbox** (trả mã 99, cần liên hệ VNPay để mở), nên khi demo hãy dùng "Ghi nhận đã hoàn thủ công".

Chạy trên máy cá nhân thì kết quả được ghi nhận khi VNPay đưa trình duyệt quay về web. URL IPN (`/thanh-toan/vnpay/ipn`, VNPay gọi từ server của họ) chỉ hoạt động khi web có địa chỉ public (VD: deploy hoặc dùng ngrok) và đã khai báo URL này trong trang quản lý sandbox.

**Email** (quên mật khẩu, vé điện tử): mặc định `MAIL_MAILER=log` nên email được ghi vào `storage/logs/laravel.log`. Muốn gửi thật, điền cấu hình SMTP (VD: [Mailtrap](https://mailtrap.io) hoặc Gmail + App Password) vào các biến `MAIL_*` trong `.env`.

### Chạy kiểm thử tự động

```bash
php artisan test
```

97 test chức năng (đăng nhập, phân quyền 3 vai trò, thống kê, quên mật khẩu, tìm kiếm, đặt vé, mã giảm giá, thanh toán VNPay, hoàn tiền, soát vé, đánh giá, xuất Excel, lịch chiếu tự động, CRUD quản trị) chạy trên SQLite in-memory nên không ảnh hưởng dữ liệu thật.

---

## 3. Chức năng

### Khách (chưa đăng nhập)
- Xem phim đang chiếu / sắp chiếu, chi tiết phim, trailer YouTube.
- **Tìm kiếm** phim theo tên, đạo diễn, diễn viên + lọc theo thể loại, trạng thái, **phân trang giữ từ khóa**.
- **Gợi ý tìm kiếm trực tiếp bằng AJAX** trên thanh menu.
- Xem lịch chiếu theo ngày (phim sắp chiếu được **mở bán vé trước** khoảng 1 tuần trước khởi chiếu, có nhãn "Đặt vé trước" trên thẻ phim; phim còn xa ngày chiếu hiện "Mở bán vé từ dd/mm"), xem **đánh giá của khán giả** (điểm trung bình, phân bố số sao).
- Đăng ký, đăng nhập, **quên mật khẩu** (gửi link đặt lại qua email).
- Trang **Ưu đãi**: chương trình giá vé (Thứ Ba vui vẻ đồng giá, suất sáng giá rẻ, đặt vé trước), mã giảm giá đang áp dụng (sao chép mã 1 chạm), **bảng giá vé** theo khung giờ / ngày trong tuần, loại ghế và phòng chiếu.
- Trang **Hỗ trợ**: câu hỏi thường gặp (đặt vé, thanh toán, hủy vé, hoàn tiền, mã giảm giá, phân loại độ tuổi) và thông tin liên hệ.

### Khách hàng (role `user`)
- **Chọn ghế trên sơ đồ phòng chiếu**: ghế thường / ghế VIP (phụ thu), tính tiền tức thì.
- Sơ đồ ghế **tự động cập nhật bằng AJAX mỗi 10 giây**; nếu ghế đang chọn bị người khác đặt sẽ được thông báo.
- Đặt vé an toàn: dùng **transaction + khóa dòng (`lockForUpdate`)** để 2 người không thể đặt trùng ghế.
- **Bắp nước**: chọn combo (bắp, nước, combo đôi, gia đình…) ngay dưới sơ đồ ghế bằng nút +/−, tổng tiền cập nhật tức thì; mã giảm giá chỉ trừ vào tiền vé. Bắp nước hiện trên vé, email và trang soát vé để nhân viên giao khi khách vào rạp.
- **Mã giảm giá**: danh sách "Mã dành cho bạn" ngay ở bước chọn ghế (hiện số tiền được giảm theo ghế đang chọn, gợi ý mã tiết kiệm nhất, báo còn thiếu bao nhiêu để đủ điều kiện), bấm để áp dụng; kiểm tra bằng AJAX, tự tính lại khi đổi ghế (giảm % có mức tối đa / giảm tiền cố định, đơn tối thiểu, giới hạn lượt, mỗi khách 1 lần).
- Vé điện tử có mã vé + mã QR (tạo ngay trên server, không cần mạng), **bấm để phóng to QR** nền trắng cho nhân viên quét, in vé, **nhận vé qua email** ngay sau khi đặt.
- Có vé xem trong hôm nay thì **trang chủ nhắc ngay đầu trang** (phim, giờ, phòng, ghế) kèm nút **"Mở mã QR"**: đến rạp chỉ cần mở web là đưa vé cho nhân viên soát.
- **Thanh toán online qua VNPay** (thẻ ATM, Visa/Master, QR ngân hàng) ngay trên trang vé, hoặc trả tại quầy khi nhận vé; kết quả được kiểm tra chữ ký HMAC-SHA512 và số tiền trước khi ghi nhận.
- **Đánh giá phim** (1–5 sao + bình luận) sau khi đã xem.
- Lịch sử đặt vé (sắp chiếu / đã chiếu) kèm trạng thái thanh toán / hoàn tiền. Khách **tự hủy được vé chưa thanh toán** khi còn ít nhất 60 phút trước giờ chiếu; vé đã thanh toán không đổi trả khi khách đổi ý (giống các rạp), chỉ được rạp hoàn tiền khi có sự cố.
- Cập nhật hồ sơ, **upload ảnh đại diện**, đổi mật khẩu.

### Nhân viên soát vé (role `staff`)
- Đăng nhập vào thẳng trang **Soát vé**, menu chỉ có mục này; vào các trang quản trị khác bị chặn (403). Soát vé, thu tiền vé chưa thanh toán khi cho khách vào, nhưng không xem / sửa được đơn đặt vé.

### Quản trị viên (role `admin`, khu vực `/admin`)
- **Tổng quan**: doanh thu tháng, vé bán hôm nay, biểu đồ doanh thu 14 ngày (Chart.js), top phim.
- **Thống kê** (`/admin/thong-ke`): chọn khoảng ngày chiếu (hoặc nhanh: hôm nay, 7/30 ngày, tháng này, tháng trước) → doanh thu vé / bắp nước, **tỉ lệ lấp đầy ghế**, biểu đồ doanh thu theo ngày, **hiệu quả từng khung giờ** (sáng / chiều / tối / khuya, kèm phim bán chạy nhất mỗi khung), top phim (doanh thu + lấp đầy), bắp nước bán chạy.
- **CRUD Phim**: upload poster, gán nhiều thể loại, ngày kết thúc chiếu (tự chuyển "Ngừng chiếu"), **xóa mềm (Soft Delete)** → thùng rác → khôi phục / xóa vĩnh viễn.
- **CRUD Thể loại**, **CRUD Phòng chiếu** (xem trước sơ đồ ghế khi nhập số hàng/ghế).
- **CRUD Suất chiếu**: tự tính giờ kết thúc, **chặn trùng giờ trong cùng phòng**, khóa sửa/xóa khi đã có vé; **hủy suất khi có sự cố**: hủy mọi vé, hoàn tiền vé đã thanh toán, email từng khách, suất được xóa mềm.
- **Soát vé** (`/admin/soat-ve`): quét mã QR trên vé bằng camera laptop/điện thoại (hoặc máy quét mã vạch, gõ mã vé) → hiện ngay phim, suất, phòng, ghế, trạng thái thanh toán → bấm **"Cho vào"** (vé chưa trả tiền thì thu tiền rồi cho vào). Chặn vé đã hủy, vé quét lần 2, vé chưa tới giờ (mở soát trước giờ chiếu 60 phút) hoặc suất đã kết thúc; có tiếng bíp báo kết quả và danh sách vé đã soát trong ngày. Bảng **"Suất đang soát vé"** cho biết suất nào đã tới giờ soát (kèm số vé đã vào / đã bán, vé chưa thanh toán) và suất nào sắp mở; tự cập nhật mỗi 30 giây, **báo thông báo + tiếng bíp khi có suất vừa mở soát**, menu admin hiện số suất đang soát.
- **Đơn đặt vé**: tìm kiếm theo mã vé/khách/phim, lọc trạng thái, thanh toán & khoảng ngày, xác nhận/hủy, **xác nhận đã thu tiền tại quầy**, **hủy vé & hoàn tiền** (API hoàn tiền VNPay hoặc hoàn thủ công, cảnh báo vé "Chờ hoàn tiền"), xóa đơn đã hủy chưa có giao dịch tiền, **xuất Excel** (chi tiết đơn + doanh thu theo phim).
- **Mã giảm giá**: CRUD mã, thống kê lượt dùng và tổng tiền đã giảm.
- **Bắp nước**: CRUD combo (upload ảnh, giá, bật/tắt bán), thống kê số phần đã bán và doanh thu; combo đã bán không xóa được (chỉ ngừng bán), đổi giá không ảnh hưởng đơn cũ.
- **Đánh giá**: tìm kiếm, lọc theo số sao, ẩn/hiện bình luận vi phạm, xóa.
- **Người dùng**: tạo/sửa/xóa tài khoản, phân quyền quản trị viên / nhân viên soát vé / khách hàng.

---

## 4. Kỹ thuật Laravel áp dụng

| Kỹ thuật | Vị trí trong mã nguồn |
|---|---|
| Migration (12 bảng nghiệp vụ, khóa ngoại, unique, index; thêm cột / đổi enum bằng migration mới) | `database/migrations/` |
| Seeder + Factory | `database/seeders/`, `database/factories/` |
| Eloquent: `hasMany`, `belongsTo`, `belongsToMany`, scope, accessor | `app/Models/` |
| Soft Delete | `Movie` + `Admin\MovieController@trash/restore/forceDelete`; `Showtime` (suất bị rạp hủy) |
| Middleware phân quyền tự viết `role:admin`, `role:admin,staff` | `app/Http/Middleware/EnsureUserHasRole.php`, `bootstrap/app.php` |
| Policy (chỉ chủ vé được xem/hủy/thanh toán vé, chỉ chủ đánh giá được xóa) | `app/Policies/` |
| Form Request + validate tùy biến (`after()`) | `app/Http/Requests/` |
| `validate()` trong Controller | `ProfileController@updatePassword`, `Admin\BookingController@update` |
| Thông báo lỗi tiếng Việt | `lang/vi/validation.php` |
| Flash message (toast) | `resources/views/partials/flash.blade.php` |
| Upload file qua Storage | `ProfileController@update`, `Admin\MovieController@store/update` |
| AJAX (JSON API) | `MovieController@suggest`, `BookingController@seatStatus/checkVoucher`, `public/js/` |
| Blade layout, component, directive `@money`, View Composer (số suất đang soát vé trên menu) | `resources/views/layouts`, `components`, `AppServiceProvider` |
| DB Transaction + `lockForUpdate` (chống đặt trùng ghế, ghi nhận thanh toán / soát vé / hoàn tiền 1 lần) | `BookingController@store`, `VnpayGateway::process`, `Admin\CheckInController@store`, `BookingRefunder` |
| Service class (tách nghiệp vụ khỏi Controller) | `app/Services/` – `VnpayGateway`, `BookingRefunder`, `ShowtimeCanceller`, `UnpaidBookingReleaser`, `SalesReport`, `ShowtimeScheduler`, `TicketQr` |
| Gọi API ngoài bằng HTTP Client (VNPay hoàn tiền), giả lập bằng `Http::fake` khi test | `VnpayGateway::refund`, `tests/Feature/RefundTest.php` |
| Feature Test | `tests/Feature/` |
| Artisan command + HTTP Client (cào dữ liệu) | `app/Console/Commands/CrawlMovies.php` |
| Task Scheduling (xếp lịch chiếu hằng ngày, tự hủy vé quá hạn mỗi phút, lấy phim mới hằng tuần) | `app/Console/Commands/`, `app/Services/ShowtimeScheduler.php`, `routes/console.php` |
| Gửi email: Password Broker, Notification, Mailable (Markdown, ảnh QR nhúng) | `PasswordResetController`, `app/Notifications/`, `app/Mail/` (vé điện tử, vé tự hủy, rạp hủy vé / hoàn tiền) |
| Xuất file Excel (.xlsx) | `app/Exports/BookingsExport.php` (thư viện `openspout/openspout`) |

## 5. Cấu trúc thư mục chính

```
app/
├── Console/Commands/          # movies:crawl (cào phim từ Moveek), showtimes:refresh (lịch chiếu hằng ngày), bookings:release-unpaid (tự hủy vé quá hạn thanh toán)
├── Http/Controllers/          # Controller phía khách
│   └── Admin/                 # Controller khu vực quản trị
├── Http/Middleware/EnsureUserHasRole.php
├── Http/Requests/             # Form Request (validation)
├── Models/                    # User, Genre, Movie, Room, Showtime, Booking, BookingSeat, BookingCombo, Combo, Voucher, Review
├── Services/                  # VNPay, hoàn tiền, hủy suất, tự hủy vé quá hạn, thống kê, xếp lịch chiếu, mã QR
├── Exports/BookingsExport.php # Xuất Excel
├── Mail/                      # Email vé điện tử, vé tự hủy, rạp hủy vé / hoàn tiền
└── Policies/                  # BookingPolicy, ReviewPolicy
config/cinema.php              # Cấu hình nghiệp vụ: phụ thu VIP, thời gian dọn phòng...
database/
├── migrations/  seeders/  factories/
└── cinebook.sql               # File export CSDL
lang/vi/                       # Ngôn ngữ tiếng Việt
public/css, public/js          # Giao diện + script AJAX
resources/views/
├── layouts/ (app, admin)  components/  partials/
├── home, movies/, schedule/, bookings/, profile/, auth/, pages/ (ưu đãi, hỗ trợ), emails/
└── admin/ (dashboard, reports, check-in, movies, genres, rooms, showtimes, bookings, vouchers, combos, reviews, users)
docs/
├── THIET_KE.md                # ERD, Use Case, đặc tả chức năng
├── PHAN_CONG.md               # Phân công: mỗi thành viên phụ trách trang / file nào, câu hỏi khi bảo vệ
└── screenshots/               # Ảnh chụp màn hình (xem mục 9)
```

## 6. Cấu hình nghiệp vụ (`config/cinema.php`)

| Khóa | Mặc định | Ý nghĩa |
|---|---|---|
| `vip_surcharge` | 20.000 ₫ | Phụ thu mỗi ghế VIP |
| `cleaning_minutes` | 15 phút | Thời gian dọn phòng giữa 2 suất |
| `max_seats_per_booking` | 8 | Số ghế tối đa mỗi lần đặt |
| `cancel_before_minutes` | 60 phút | Khách được tự hủy vé trước giờ chiếu |
| `checkin_before_minutes` | 60 phút | Mở soát vé cho vào rạp trước giờ chiếu |
| `unpaid_release_minutes` | 30 phút | Hạn thanh toán trước giờ chiếu, quá hạn vé tự hủy |
| `payment_grace_minutes` | 15 phút | Thời gian tối thiểu để thanh toán khi đặt sát giờ |
| `seed_history_days` | 35 ngày | Dữ liệu mẫu có lịch sử suất chiếu / đơn đặt vé bao nhiêu ngày (máy yếu có thể đặt `SEED_HISTORY_DAYS=14` trong `.env` cho seed nhanh hơn) |

## 7. Thư viện & dịch vụ bên ngoài

- [Laravel 13](https://laravel.com) – framework PHP (MIT)
- [Bootstrap 5.3](https://getbootstrap.com) & [Bootstrap Icons](https://icons.getbootstrap.com) – giao diện (MIT)
- [Chart.js 4](https://www.chartjs.org) – biểu đồ doanh thu (MIT)
- Font [Be Vietnam Pro](https://fonts.google.com/specimen/Be+Vietnam+Pro) – Google Fonts (OFL)
- [chillerlan/php-qrcode](https://github.com/chillerlan/php-qrcode) – tạo mã QR trên vé và trong email ngay trên server (MIT)
- [html5-qrcode](https://github.com/mebjas/html5-qrcode) – quét mã QR bằng camera ở trang Soát vé (Apache-2.0)
- [VNPay sandbox](https://sandbox.vnpayment.vn/apis/) – cổng thanh toán online (môi trường thử nghiệm)
- YouTube embed – phát trailer phim

## 8. Xử lý sự cố thường gặp

| Lỗi | Cách khắc phục |
|---|---|
| `could not find driver` | Bật `extension=pdo_mysql` trong `php.ini` |
| `SQLSTATE[HY000] [1049] Unknown database` | Chưa tạo database `cinebook` |
| Poster/avatar upload không hiển thị | Chạy `php artisan storage:link`, kiểm tra `APP_URL` đúng cổng đang chạy |
| `No application encryption key` | Chạy `php artisan key:generate` |
| Lỗi 419 Page Expired | Tải lại trang (phiên đăng nhập hết hạn) |
| Vé quá hạn thanh toán không tự hủy | Bộ lập lịch chưa chạy: mở thêm terminal chạy `php artisan schedule:work` |
| Hoàn tiền qua VNPay báo lỗi 99 | VNPay khóa API hoàn tiền trên tài khoản sandbox; dùng "Ghi nhận đã hoàn thủ công" hoặc liên hệ VNPay để mở |
| Trang Soát vé không mở được camera | Trình duyệt chỉ cho dùng camera trên `localhost` hoặc HTTPS: mở bằng `http://localhost:8000` và bấm "Cho phép" khi được hỏi. Mở từ điện thoại qua IP mạng LAN (`http://192.168...`) sẽ bị chặn, khi đó nhập mã vé bằng tay |

## 9. Ảnh chụp màn hình

Tất cả nằm trong [`docs/screenshots/`](docs/screenshots/) (chụp ngày 28/10/2026 với dữ liệu mẫu).

| Khu vực | Ảnh |
|---|---|
| Khách | [Trang chủ](docs/screenshots/01-trang-chu.png) · [Tìm kiếm phim](docs/screenshots/02-tim-kiem-phim.png) · [Chi tiết phim](docs/screenshots/03-chi-tiet-phim.png) · [Lịch chiếu](docs/screenshots/04-lich-chieu.png) · [Đăng nhập](docs/screenshots/05-dang-nhap.png) · [Đăng ký báo lỗi](docs/screenshots/06-dang-ky-loi-validate.png) · [Gợi ý tìm kiếm AJAX](docs/screenshots/07-tim-kiem-ajax.png) · [Ưu đãi](docs/screenshots/15-uu-dai.png) |
| Khách hàng | [Chọn ghế + mã giảm giá + bắp nước](docs/screenshots/08-chon-ghe.png) · [Vé điện tử](docs/screenshots/09-ve-dien-tu.png) · [Vé của tôi](docs/screenshots/10-ve-cua-toi.png) · [Tài khoản](docs/screenshots/11-tai-khoan.png) · [Khách vào /admin bị chặn 403](docs/screenshots/12-user-bi-chan-admin-403.png) · [Nhắc vé hôm nay](docs/screenshots/16-ve-hom-nay.png) · [Phóng to QR](docs/screenshots/17-phong-to-qr.png) |
| Điện thoại | [Trang chủ](docs/screenshots/13-mobile-trang-chu.png) · [Chọn ghế](docs/screenshots/14-mobile-chon-ghe.png) · [Admin](docs/screenshots/29-admin-mobile.png) |
| Quản trị | [Tổng quan](docs/screenshots/20-admin-dashboard.png) · [Phim](docs/screenshots/21-admin-phim.png) · [Thêm phim](docs/screenshots/22-admin-them-phim.png) · [Thêm phim báo lỗi](docs/screenshots/23-admin-them-phim-loi.png) · [Thể loại](docs/screenshots/24-admin-the-loai.png) · [Sửa phòng](docs/screenshots/25-admin-sua-phong.png) · [Suất chiếu](docs/screenshots/26-admin-suat-chieu.png) · [Đơn đặt vé](docs/screenshots/27-admin-don-dat-ve.png) · [Người dùng](docs/screenshots/28-admin-nguoi-dung.png) · [Thống kê](docs/screenshots/30-admin-thong-ke.png) · [Soát vé](docs/screenshots/31-admin-soat-ve.png) · [Hoàn tiền](docs/screenshots/32-admin-hoan-tien.png) · [Mã giảm giá](docs/screenshots/33-admin-ma-giam-gia.png) · [Bắp nước](docs/screenshots/34-admin-bap-nuoc.png) |
| Nhân viên soát vé | [Trang Soát vé (chỉ có mục này)](docs/screenshots/35-nhan-vien-soat-ve.png) |
