# 🎬 CineBook – Website đặt vé xem phim

Đồ án cuối môn **Lập trình mã nguồn mở** – xây dựng bằng **Laravel 13 + MySQL + Blade/Bootstrap 5**.

CineBook cho phép khách hàng xem phim đang chiếu, tra lịch chiếu, **chọn ghế trực tiếp trên sơ đồ phòng chiếu** và đặt vé online; quản trị viên quản lý phim, thể loại, phòng chiếu, suất chiếu, đơn đặt vé, người dùng và theo dõi doanh thu.

---

## 1. Yêu cầu môi trường

| Phần mềm | Phiên bản |
|---|---|
| PHP | ≥ 8.3 (bật extension `pdo_mysql`, `mbstring`, `fileinfo`, `gd`) |
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

Danh sách phim (tên, mô tả, đạo diễn, diễn viên, thời lượng, ngày khởi chiếu, phân loại tuổi, thể loại, trailer, poster) được cào từ [Moveek.com](https://moveek.com) – phim **đang chiếu** và **sắp chiếu** tại Việt Nam – và lưu sẵn trong `database/seeders/data/` (`movies.json` + thư mục `posters/`), nên `migrate --seed` không cần Internet. Để cập nhật phim mới nhất:

```bash
php artisan movies:crawl                 # mặc định: toàn bộ phim đang chiếu + 20 phim sắp chiếu
php artisan movies:crawl --upcoming=40   # lấy nhiều phim sắp chiếu hơn
php artisan migrate:fresh --seed         # nạp lại database (xóa dữ liệu cũ)
```

Phòng chiếu, suất chiếu, người dùng và đơn đặt vé vẫn được sinh tự động bởi seeder. Phim sắp chiếu chưa công bố thời lượng được tạm tính 120 phút; phim chưa có phân loại tuổi được để mặc định `P`.

### Tài khoản demo (mật khẩu đều là `password`)

| Vai trò | Email | Đăng nhập xong sẽ vào |
|---|---|---|
| Quản trị viên | `admin@cinebook.test` | `/admin` |
| Khách hàng | `user@cinebook.test` | Trang chủ |

### Chạy kiểm thử tự động

```bash
php artisan test
```

26 test chức năng (đăng nhập, phân quyền, tìm kiếm, đặt vé, CRUD quản trị) chạy trên SQLite in-memory nên không ảnh hưởng dữ liệu thật.

---

## 3. Chức năng

### Khách (chưa đăng nhập)
- Xem phim đang chiếu / sắp chiếu, chi tiết phim, trailer YouTube.
- **Tìm kiếm** phim theo tên, đạo diễn, diễn viên + lọc theo thể loại, trạng thái, **phân trang giữ từ khóa**.
- **Gợi ý tìm kiếm trực tiếp bằng AJAX** trên thanh menu.
- Xem lịch chiếu theo ngày.
- Đăng ký, đăng nhập.

### Khách hàng (role `user`)
- **Chọn ghế trên sơ đồ phòng chiếu**: ghế thường / ghế VIP (phụ thu), tính tiền tức thì.
- Sơ đồ ghế **tự động cập nhật bằng AJAX mỗi 10 giây**; nếu ghế đang chọn bị người khác đặt sẽ được thông báo.
- Đặt vé an toàn: dùng **transaction + khóa dòng (`lockForUpdate`)** để 2 người không thể đặt trùng ghế.
- Vé điện tử có mã vé + mã QR, in vé.
- Lịch sử đặt vé (sắp chiếu / đã chiếu), **tự hủy vé** trước giờ chiếu 60 phút.
- Cập nhật hồ sơ, **upload ảnh đại diện**, đổi mật khẩu.

### Quản trị viên (role `admin`, khu vực `/admin`)
- **Tổng quan**: doanh thu tháng, vé bán hôm nay, biểu đồ doanh thu 14 ngày (Chart.js), top phim.
- **CRUD Phim**: upload poster, gán nhiều thể loại, **xóa mềm (Soft Delete)** → thùng rác → khôi phục / xóa vĩnh viễn.
- **CRUD Thể loại**, **CRUD Phòng chiếu** (xem trước sơ đồ ghế khi nhập số hàng/ghế).
- **CRUD Suất chiếu**: tự tính giờ kết thúc, **chặn trùng giờ trong cùng phòng**, khóa sửa/xóa khi đã có vé.
- **Đơn đặt vé**: tìm kiếm theo mã vé/khách/phim, lọc trạng thái & khoảng ngày, xác nhận/hủy, xóa đơn đã hủy.
- **Người dùng**: tạo/sửa/xóa tài khoản, phân quyền admin/user.

---

## 4. Kỹ thuật Laravel áp dụng

| Kỹ thuật | Vị trí trong mã nguồn |
|---|---|
| Migration (7 bảng nghiệp vụ, khóa ngoại, unique, index) | `database/migrations/` |
| Seeder + Factory | `database/seeders/`, `database/factories/` |
| Eloquent: `hasMany`, `belongsTo`, `belongsToMany`, scope, accessor | `app/Models/` |
| Soft Delete | `Movie` + `Admin\MovieController@trash/restore/forceDelete` |
| Middleware phân quyền tự viết `role:admin` | `app/Http/Middleware/EnsureUserHasRole.php`, `bootstrap/app.php` |
| Policy (chỉ chủ vé được xem/hủy vé) | `app/Policies/BookingPolicy.php` |
| Form Request + validate tùy biến (`after()`) | `app/Http/Requests/` |
| `validate()` trong Controller | `ProfileController@updatePassword`, `Admin\BookingController@update` |
| Thông báo lỗi tiếng Việt | `lang/vi/validation.php` |
| Flash message (toast) | `resources/views/partials/flash.blade.php` |
| Upload file qua Storage | `ProfileController@update`, `Admin\MovieController@store/update` |
| AJAX (JSON API) | `MovieController@suggest`, `BookingController@seatStatus`, `public/js/` |
| Blade layout, component, directive `@money` | `resources/views/layouts`, `components`, `AppServiceProvider` |
| DB Transaction + `lockForUpdate` | `BookingController@store` |
| Feature Test | `tests/Feature/` |
| Artisan command + HTTP Client (cào dữ liệu) | `app/Console/Commands/CrawlMovies.php` |

## 5. Cấu trúc thư mục chính

```
app/
├── Console/Commands/          # movies:crawl – cào phim thật từ Moveek
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
