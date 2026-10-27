<?php

namespace App\Services;

use chillerlan\QRCode\Output\QRGdImagePNG;
use chillerlan\QRCode\Output\QRMarkupSVG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

/**
 * Tạo mã QR của vé ngay trên server (không gọi dịch vụ ngoài, chạy được cả khi mất mạng).
 * QR chỉ chứa mã vé, nhân viên quét tại quầy để tra đơn.
 */
class TicketQr
{
    /** Ảnh SVG dạng data URI để đặt thẳng vào thẻ <img> trên trang web */
    public static function svg(string $code): string
    {
        return (new QRCode(new QROptions([
            'outputInterface' => QRMarkupSVG::class,
            'quietzoneSize' => 1, // khung ảnh trên trang vé đã có nền trắng bao quanh
        ])))->render($code);
    }

    /** Ảnh PNG (dữ liệu nhị phân) để nhúng vào email – nhiều hộp thư như Gmail chặn ảnh SVG/data URI */
    public static function png(string $code): string
    {
        return (new QRCode(new QROptions([
            'outputInterface' => QRGdImagePNG::class,
            'outputBase64' => false,
            'scale' => 6,
            'addQuietzone' => true,
        ])))->render($code);
    }
}
