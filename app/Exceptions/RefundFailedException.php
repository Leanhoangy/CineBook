<?php

namespace App\Exceptions;

use RuntimeException;

/** Không hoàn tiền được (VNPay từ chối, lỗi kết nối...). Message hiển thị thẳng cho quản trị viên. */
class RefundFailedException extends RuntimeException {}
