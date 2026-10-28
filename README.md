# 🎬 CineBook – Website đặt vé xem phim

Đồ án cuối môn **Lập trình mã nguồn mở** – xây dựng bằng **Laravel 13 + MySQL + Blade/Bootstrap 5**.

CineBook cho phép khách hàng xem phim đang chiếu, tra lịch chiếu, **chọn ghế trực tiếp trên sơ đồ phòng chiếu** và đặt vé online; quản trị viên quản lý phim, thể loại, phòng chiếu, suất chiếu, đơn đặt vé, người dùng và theo dõi doanh thu.

---

## 1. Yêu cầu môi trường

| Phần mềm | Phiên bản |
|---|---|
| PHP | ≥ 8.3 (bật extension `pdo_mysql`, `mbstring`, `fileinfo`, `gd`, `zip`) |
| Composer | 2.x |
| MySQL / MariaDB | MySQL 8+ hoặc MariaDB 10.6+ (XAMPP, Laragon, Herd đều dùng được) |

> Giao diện dùng Bootstrap 5 qua CDN nên **không cần cài Node.js/npm**. Máy cần có Internet để tải CSS/JS từ CDN.

## 2. Cài đặt và chạy dự án

```bash
# 1. Cài thư viện PHP
composer install

# 2. Tạo file cấu hình
cp .env.example .env          # Windows: copy .env.example .env
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

**Cách khác:** import trực tiếp file [`database/cinebook.sql`](database/cinebook.sql) (~5 MB, khoảng 17.000 đơn đặt vé mẫu) vào database `cinebook` bằng phpMyAdmin thay cho bước 4; nếu phpMyAdmin báo file quá lớn thì dùng `migrate --seed` (mất khoảng 30 giây). Khi đó cần tự chép poster: copy các file trong `database/seeders/data/posters/` vào `storage/app/public/posters/` (nếu chạy `migrate --seed` thì seeder tự chép).

### Dữ liệu phim thật

Danh sách phim (tên, mô tả, đạo diễn, diễn viên, thời lượng, ngày khởi chiếu, phân loại tuổi, thể loại, trailer, poster) được cào từ [Moveek.com](https://moveek.com) – phim **đang chiếu**, **sắp chiếu** và một số phim **đã ngừng chiếu** gần đây tại Việt Nam – và lưu sẵn trong `database/seeders/data/` (`movies.json` + thư mục `posters/`), nên `migrate --seed` không cần Internet. Để cập nhật phim mới nhất:

```bash
php artisan movies:crawl                 # mặc định: phim đang chiếu + 20 phim sắp chiếu + 15 phim đã ngừng chiếu
php artisan movies:crawl --upcoming=40   # lấy nhiều phim sắp chiếu hơn
php artisan migrate:fresh --seed         # nạp lại database (xóa dữ liệu cũ)
php artisan movies:crawl --ended=0 --import   # chỉ lấy phim đang/sắp chiếu và thêm phim mới vào database, KHÔNG xóa dữ liệu cũ
```

Trên server thật (`APP_ENV=production`), lệnh `movies:crawl --ended=0 --import` được lên lịch chạy **3h sáng thứ Hai hằng tuần** để danh sách phim sắp chiếu không bị cạn. Phim chưa có thì thêm mới, phim đã có thì giữ nguyên (chỉ gia hạn ngày kết thúc chiếu nếu Moveek vẫn còn chiếu).

> Lệnh cào nghỉ 2 giây giữa các request. Nếu Moveek tạm chặn (lỗi 403), lệnh sẽ dừng và **giữ nguyên dữ liệu cũ** – hãy thử lại sau vài giờ.

Phòng chiếu, suất chiếu, người dùng và đơn đặt vé vẫn được sinh tự động bởi seeder, mô phỏng rạp thật: phim mới suất tối / cuối tuần gần kín, suất sáng phim cũ vắng; khách đi theo nhóm ngồi liền nhau, thích giữa và cuối rạp, 2 hàng sát màn hình chỉ có người khi suất gần kín. Moveek không công bố ngày kết thúc chiếu nên được ước tính 8 tuần sau khởi chiếu (phim chiếu lại có ngày khởi chiếu cũ thì chiếu thêm 2–4 tuần); admin sửa được trong form phim. Phim sắp chiếu chưa công bố thời lượng được tạm tính 120 phút; phim chưa có phân loại tuổi được để mặc định `P`. Trang khách chỉ hiện những thể loại còn phim đang/sắp chiếu.

### Lịch chiếu luôn cập nhật

Seeder chỉ tạo suất chiếu đến 7 ngày sau ngày seed. Để web luôn có suất chiếu (nhất là **trước buổi demo**), chạy:

```bash
php artisan showtimes:refresh   # cập nhật trạng thái phim + xếp suất chiếu 7 ngày tới
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
| Khách hàng | `user@cinebook.test` | Trang chủ |

**Mã giảm giá mẫu** (tài khoản demo chưa dùng mã nào): `CINEBOOK10` (giảm 10%, tối đa 30.000 ₫), `GIAM20K` (đơn từ 150.000 ₫), `SINHVIEN`, `NHOMBAN4` (đơn từ 300.000 ₫); `THANHVIENMOI` (20 khách đầu tiên) đã hết lượt, `HE2026` đã hết hạn và `GIANGSINH` chưa tới ngày để demo thông báo lỗi.

**Thanh toán online VNPay (sandbox, không mất tiền thật):** không cấu hình thì web chỉ nhận thanh toán tại quầy. Muốn demo thanh toán online:
1. Đăng ký tài khoản thử nghiệm tại [sandbox.vnpayment.vn/devreg](https://sandbox.vnpayment.vn/devreg/), VNPay gửi **mã website (TMN Code)** và **chuỗi bí mật (Hash Secret)** qua email.
2. Điền vào `.env`: `VNPAY_TMN_CODE=...` và `VNPAY_HASH_SECRET=...`
3. Đặt vé → trang vé bấm **"Thanh toán ... qua VNPay"** → chọn *Thẻ nội địa* → ngân hàng **NCB**, dùng thẻ test: số thẻ `9704198526191432198`, tên `NGUYEN VAN A`, ngày phát hành `07/15`, OTP `123456`.

Chạy trên máy cá nhân thì kết quả được ghi nhận khi VNPay đưa trình duyệt quay về web. URL IPN (`/thanh-toan/vnpay/ipn`, VNPay gọi từ server của họ) chỉ hoạt động khi web có địa chỉ public (VD: deploy hoặc dùng ngrok) và đã khai báo URL này trong trang quản lý sandbox.

**Email** (quên mật khẩu, vé điện tử): mặc định `MAIL_MAILER=log` nên email được ghi vào `storage/logs/laravel.log`. Muốn gửi thật, điền cấu hình SMTP (VD: [Mailtrap](https://mailtrap.io) hoặc Gmail + App Password) vào các biến `MAIL_*` trong `.env`.

### Chạy kiểm thử tự động

```bash
php artisan test
```

83 test chức năng (đăng nhập, phân quyền, quên mật khẩu, tìm kiếm, đặt vé, mã giảm giá, thanh toán VNPay, soát vé, đánh giá, xuất Excel, lịch chiếu tự động, CRUD quản trị) chạy trên SQLite in-memory nên không ảnh hưởng dữ liệu thật.

---

## 3. Chức năng

### Khách (chưa đăng nhập)
- Xem phim đang chiếu / sắp chiếu, chi tiết phim, trailer YouTube.
- **Tìm kiếm** phim theo tên, đạo diễn, diễn viên + lọc theo thể loại, trạng thái, **phân trang giữ từ khóa**.
- **Gợi ý tìm kiếm trực tiếp bằng AJAX** trên thanh menu.
- Xem lịch chiếu theo ngày (phim sắp chiếu được **mở bán vé trước** khoảng 1 tuần trước khởi chiếu, có nhãn "Đặt vé trước" trên thẻ phim; phim còn xa ngày chiếu hiện "Mở bán vé từ dd/mm"), xem **đánh giá của khán giả** (điểm trung bình, phân bố số sao).
- Đăng ký, đăng nhập, **quên mật khẩu** (gửi link đặt lại qua email).
- Trang **Ưu đãi**: chương trình giá vé (Thứ Ba vui vẻ đồng giá, suất sáng giá rẻ, đặt vé trước), mã giảm giá đang áp dụng (sao chép mã 1 chạm), **bảng giá vé** theo khung giờ / ngày trong tuần, loại ghế và phòng chiếu.
- Trang **Hỗ trợ**: câu hỏi thường gặp (đặt vé, hủy vé, mã giảm giá, phân loại độ tuổi) và thông tin liên hệ.

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
- Lịch sử đặt vé (sắp chiếu / đã chiếu) kèm trạng thái thanh toán, **tự hủy vé** chưa thanh toán trước giờ chiếu 60 phút (vé đã thanh toán online không tự hủy được).
- Cập nhật hồ sơ, **upload ảnh đại diện**, đổi mật khẩu.

### Quản trị viên (role `admin`, khu vực `/admin`)
- **Tổng quan**: doanh thu tháng, vé bán hôm nay, biểu đồ doanh thu 14 ngày (Chart.js), top phim.
- **CRUD Phim**: upload poster, gán nhiều thể loại, ngày kết thúc chiếu (tự chuyển "Ngừng chiếu"), **xóa mềm (Soft Delete)** → thùng rác → khôi phục / xóa vĩnh viễn.
- **CRUD Thể loại**, **CRUD Phòng chiếu** (xem trước sơ đồ ghế khi nhập số hàng/ghế).
- **CRUD Suất chiếu**: tự tính giờ kết thúc, **chặn trùng giờ trong cùng phòng**, khóa sửa/xóa khi đã có vé.
- **Soát vé** (`/admin/soat-ve`): quét mã QR trên vé bằng camera laptop/điện thoại (hoặc máy quét mã vạch, gõ mã vé) → hiện ngay phim, suất, phòng, ghế, trạng thái thanh toán → bấm **"Cho vào"** (vé chưa trả tiền thì thu tiền rồi cho vào). Chặn vé đã hủy, vé quét lần 2, vé chưa tới giờ (mở soát trước giờ chiếu 60 phút) hoặc suất đã kết thúc; có tiếng bíp báo kết quả và danh sách vé đã soát trong ngày.
- **Đơn đặt vé**: tìm kiếm theo mã vé/khách/phim, lọc trạng thái, thanh toán & khoảng ngày, xác nhận/hủy, **xác nhận đã thu tiền tại quầy**, xóa đơn đã hủy, **xuất Excel** (chi tiết đơn + doanh thu theo phim).
- **Mã giảm giá**: CRUD mã, thống kê lượt dùng và tổng tiền đã giảm.
- **Bắp nước**: CRUD combo (upload ảnh, giá, bật/tắt bán), thống kê số phần đã bán và doanh thu; combo đã bán không xóa được (chỉ ngừng bán), đổi giá không ảnh hưởng đơn cũ.
- **Đánh giá**: tìm kiếm, lọc theo số sao, ẩn/hiện bình luận vi phạm, xóa.
- **Người dùng**: tạo/sửa/xóa tài khoản, phân quyền admin/user.

---

## 4. Kỹ thuật Laravel áp dụng

| Kỹ thuật | Vị trí trong mã nguồn |
|---|---|
| Migration (11 bảng nghiệp vụ, khóa ngoại, unique, index) | `database/migrations/` |
| Seeder + Factory | `database/seeders/`, `database/factories/` |
| Eloquent: `hasMany`, `belongsTo`, `belongsToMany`, scope, accessor | `app/Models/` |
| Soft Delete | `Movie` + `Admin\MovieController@trash/restore/forceDelete` |
| Middleware phân quyền tự viết `role:admin` | `app/Http/Middleware/EnsureUserHasRole.php`, `bootstrap/app.php` |
| Policy (chỉ chủ vé được xem/hủy vé, chỉ chủ đánh giá được xóa) | `app/Policies/` |
| Form Request + validate tùy biến (`after()`) | `app/Http/Requests/` |
| `validate()` trong Controller | `ProfileController@updatePassword`, `Admin\BookingController@update` |
| Thông báo lỗi tiếng Việt | `lang/vi/validation.php` |
| Flash message (toast) | `resources/views/partials/flash.blade.php` |
| Upload file qua Storage | `ProfileController@update`, `Admin\MovieController@store/update` |
| AJAX (JSON API) | `MovieController@suggest`, `BookingController@seatStatus/checkVoucher`, `public/js/` |
| Blade layout, component, directive `@money` | `resources/views/layouts`, `components`, `AppServiceProvider` |
| DB Transaction + `lockForUpdate` | `BookingController@store` |
| Feature Test | `tests/Feature/` |
| Artisan command + HTTP Client (cào dữ liệu) | `app/Console/Commands/CrawlMovies.php` |
| Task Scheduling (tự xếp lịch chiếu hằng ngày) | `app/Console/Commands/RefreshShowtimes.php`, `app/Services/ShowtimeScheduler.php`, `routes/console.php` |
| Gửi email: Password Broker, Notification, Mailable (Markdown) | `PasswordResetController`, `app/Notifications/`, `app/Mail/BookingConfirmed.php` |
| Xuất file Excel (.xlsx) | `app/Exports/BookingsExport.php` (thư viện `openspout/openspout`) |

## 5. Cấu trúc thư mục chính

```
app/
├── Console/Commands/          # movies:crawl (cào phim từ Moveek), showtimes:refresh (lịch chiếu hằng ngày), bookings:release-unpaid (tự hủy vé quá hạn thanh toán)
├── Http/Controllers/          # Controller phía khách
│   └── Admin/                 # Controller khu vực quản trị
├── Http/Middleware/EnsureUserHasRole.php
├── Http/Requests/             # Form Request (validation)
├── Models/                    # User, Genre, Movie, Room, Showtime, Booking, BookingSeat
└── Policies/BookingPolicy.php
config/cinema.php              # Cấu hình nghiệp vụ: phụ thu VIP, thời gian dọn phòng...
database/
├── migrations/  seeders/  factories/
└── cinebook.sql               # File export CSDL
lang/vi/                       # Ngôn ngữ tiếng Việt
public/css, public/js          # Giao diện + script AJAX
resources/views/
├── layouts/ (app, admin)  components/  partials/
├── home, movies/, schedule/, bookings/, profile/, auth/
└── admin/ (dashboard, movies, genres, rooms, showtimes, bookings, users)
docs/
├── THIET_KE.md                # ERD, Use Case, đặc tả chức năng
└── screenshots/               # Ảnh chụp màn hình
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
| Trang Soát vé không mở được camera | Trình duyệt chỉ cho dùng camera trên `localhost` hoặc HTTPS: mở bằng `http://localhost:8000` và bấm "Cho phép" khi được hỏi. Mở từ điện thoại qua IP mạng LAN (`http://192.168...`) sẽ bị chặn, khi đó nhập mã vé bằng tay |
