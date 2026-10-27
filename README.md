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

**Cách khác:** import trực tiếp file [`database/cinebook.sql`](database/cinebook.sql) vào database `cinebook` bằng phpMyAdmin thay cho bước 4. Khi đó cần tự chép poster: copy các file trong `database/seeders/data/posters/` vào `storage/app/public/posters/` (nếu chạy `migrate --seed` thì seeder tự chép).

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

Phòng chiếu, suất chiếu, người dùng và đơn đặt vé vẫn được sinh tự động bởi seeder. Moveek không công bố ngày kết thúc chiếu nên được ước tính 8 tuần sau khởi chiếu (phim chiếu lại có ngày khởi chiếu cũ thì chiếu thêm 2–4 tuần); admin sửa được trong form phim. Phim sắp chiếu chưa công bố thời lượng được tạm tính 120 phút; phim chưa có phân loại tuổi được để mặc định `P`. Trang khách chỉ hiện những thể loại còn phim đang/sắp chiếu.

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

### Tài khoản demo (mật khẩu đều là `password`)

| Vai trò | Email | Đăng nhập xong sẽ vào |
|---|---|---|
| Quản trị viên | `admin@cinebook.test` | `/admin` |
| Khách hàng | `user@cinebook.test` | Trang chủ |

**Mã giảm giá mẫu** (tài khoản demo chưa dùng mã nào): `CINEBOOK10` (giảm 10%, tối đa 30.000 ₫), `GIAM20K` (đơn từ 150.000 ₫), `SINHVIEN`, `NHOMBAN4` (đơn từ 300.000 ₫), `THANHVIENMOI`; `HE2026` đã hết hạn và `GIANGSINH` chưa tới ngày để demo thông báo lỗi.

**Email** (quên mật khẩu, vé điện tử): mặc định `MAIL_MAILER=log` nên email được ghi vào `storage/logs/laravel.log`. Muốn gửi thật, điền cấu hình SMTP (VD: [Mailtrap](https://mailtrap.io) hoặc Gmail + App Password) vào các biến `MAIL_*` trong `.env`.

### Chạy kiểm thử tự động

```bash
php artisan test
```

54 test chức năng (đăng nhập, phân quyền, quên mật khẩu, tìm kiếm, đặt vé, mã giảm giá, đánh giá, xuất Excel, lịch chiếu tự động, CRUD quản trị) chạy trên SQLite in-memory nên không ảnh hưởng dữ liệu thật.

---

## 3. Chức năng

### Khách (chưa đăng nhập)
- Xem phim đang chiếu / sắp chiếu, chi tiết phim, trailer YouTube.
- **Tìm kiếm** phim theo tên, đạo diễn, diễn viên + lọc theo thể loại, trạng thái, **phân trang giữ từ khóa**.
- **Gợi ý tìm kiếm trực tiếp bằng AJAX** trên thanh menu.
- Xem lịch chiếu theo ngày (phim sắp chiếu được **mở bán vé trước**), xem **đánh giá của khán giả** (điểm trung bình, phân bố số sao).
- Đăng ký, đăng nhập, **quên mật khẩu** (gửi link đặt lại qua email).

### Khách hàng (role `user`)
- **Chọn ghế trên sơ đồ phòng chiếu**: ghế thường / ghế VIP (phụ thu), tính tiền tức thì.
- Sơ đồ ghế **tự động cập nhật bằng AJAX mỗi 10 giây**; nếu ghế đang chọn bị người khác đặt sẽ được thông báo.
- Đặt vé an toàn: dùng **transaction + khóa dòng (`lockForUpdate`)** để 2 người không thể đặt trùng ghế.
- **Mã giảm giá**: kiểm tra bằng AJAX, tự tính lại khi đổi ghế (giảm % có mức tối đa / giảm tiền cố định, đơn tối thiểu, giới hạn lượt, mỗi khách 1 lần).
- Vé điện tử có mã vé + mã QR, in vé, **nhận vé qua email** ngay sau khi đặt.
- **Đánh giá phim** (1–5 sao + bình luận) sau khi đã xem.
- Lịch sử đặt vé (sắp chiếu / đã chiếu), **tự hủy vé** trước giờ chiếu 60 phút.
- Cập nhật hồ sơ, **upload ảnh đại diện**, đổi mật khẩu.

### Quản trị viên (role `admin`, khu vực `/admin`)
- **Tổng quan**: doanh thu tháng, vé bán hôm nay, biểu đồ doanh thu 14 ngày (Chart.js), top phim.
- **CRUD Phim**: upload poster, gán nhiều thể loại, ngày kết thúc chiếu (tự chuyển "Ngừng chiếu"), **xóa mềm (Soft Delete)** → thùng rác → khôi phục / xóa vĩnh viễn.
- **CRUD Thể loại**, **CRUD Phòng chiếu** (xem trước sơ đồ ghế khi nhập số hàng/ghế).
- **CRUD Suất chiếu**: tự tính giờ kết thúc, **chặn trùng giờ trong cùng phòng**, khóa sửa/xóa khi đã có vé.
- **Đơn đặt vé**: tìm kiếm theo mã vé/khách/phim, lọc trạng thái & khoảng ngày, xác nhận/hủy, xóa đơn đã hủy, **xuất Excel** (chi tiết đơn + doanh thu theo phim).
- **Mã giảm giá**: CRUD mã, thống kê lượt dùng và tổng tiền đã giảm.
- **Đánh giá**: tìm kiếm, lọc theo số sao, ẩn/hiện bình luận vi phạm, xóa.
- **Người dùng**: tạo/sửa/xóa tài khoản, phân quyền admin/user.

---

## 4. Kỹ thuật Laravel áp dụng

| Kỹ thuật | Vị trí trong mã nguồn |
|---|---|
| Migration (9 bảng nghiệp vụ, khóa ngoại, unique, index) | `database/migrations/` |
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
├── Console/Commands/          # movies:crawl (cào phim từ Moveek), showtimes:refresh (lịch chiếu hằng ngày)
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

## 7. Thư viện & dịch vụ bên ngoài

- [Laravel 13](https://laravel.com) – framework PHP (MIT)
- [Bootstrap 5.3](https://getbootstrap.com) & [Bootstrap Icons](https://icons.getbootstrap.com) – giao diện (MIT)
- [Chart.js 4](https://www.chartjs.org) – biểu đồ doanh thu (MIT)
- Font [Be Vietnam Pro](https://fonts.google.com/specimen/Be+Vietnam+Pro) – Google Fonts (OFL)
- [goQR.me API](https://goqr.me/api/) – tạo mã QR trên vé (chỉ gửi mã vé)
- YouTube embed – phát trailer phim

## 8. Xử lý sự cố thường gặp

| Lỗi | Cách khắc phục |
|---|---|
| `could not find driver` | Bật `extension=pdo_mysql` trong `php.ini` |
| `SQLSTATE[HY000] [1049] Unknown database` | Chưa tạo database `cinebook` |
| Poster/avatar upload không hiển thị | Chạy `php artisan storage:link`, kiểm tra `APP_URL` đúng cổng đang chạy |
| `No application encryption key` | Chạy `php artisan key:generate` |
| Lỗi 419 Page Expired | Tải lại trang (phiên đăng nhập hết hạn) |
