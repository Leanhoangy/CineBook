<?php

namespace App\Exports;

use App\Models\Booking;
use App\Models\BookingSeat;
use Illuminate\Database\Eloquent\Builder;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\CellAlignment;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Entity\SheetView;
use OpenSpout\Writer\XLSX\Writer;

/**
 * Xuất danh sách đơn đặt vé ra file Excel (.xlsx) gồm 2 sheet:
 * chi tiết từng đơn và bảng tổng hợp doanh thu theo phim (chỉ tính vé đã xác nhận).
 */
class BookingsExport
{
    private const MONEY = '#,##0" ₫"';

    public function __construct(private Builder $query) {}

    /** Ghi file vào $path */
    public function store(string $path): void
    {
        $writer = new Writer;
        $writer->openToFile($path);

        $this->writeBookingsSheet($writer);

        $writer->addNewSheetAndMakeItCurrent();
        $this->writeRevenueSheet($writer);

        $writer->close();
    }

    private function writeBookingsSheet(Writer $writer): void
    {
        $sheet = $writer->getCurrentSheet();
        $sheet->setName('Đơn đặt vé');
        $sheet->setSheetView((new SheetView)->setFreezeRow(2));
        $sheet->setColumnWidth(12, 1);
        $sheet->setColumnWidth(17, 2, 7);
        $sheet->setColumnWidth(24, 3, 4);
        $sheet->setColumnWidth(32, 6);
        $sheet->setColumnWidth(10, 8, 10);
        $sheet->setColumnWidth(18, 9);
        $sheet->setColumnWidth(24, 11);
        $sheet->setColumnWidth(14, 12, 13, 14, 15);
        $sheet->setColumnWidth(13, 16);
        $sheet->setColumnWidth(26, 17);

        $writer->addRow($this->headerRow([
            'Mã vé', 'Ngày đặt', 'Khách hàng', 'Email', 'Số điện thoại', 'Phim', 'Suất chiếu', 'Phòng',
            'Ghế', 'Số ghế', 'Bắp nước', 'Tạm tính', 'Mã giảm giá', 'Giảm giá', 'Thành tiền', 'Trạng thái', 'Thanh toán',
        ]));

        $money = (new Style)->setFormat(self::MONEY);
        $date = (new Style)->setFormat('dd/mm/yyyy hh:mm');

        // Đọc theo từng lô 500 đơn để không tốn bộ nhớ khi dữ liệu lớn
        (clone $this->query)
            ->with(['user', 'showtime.movie', 'showtime.room', 'seats', 'combos', 'voucher'])
            ->lazyByIdDesc(500, 'bookings.id', 'id')
            ->each(function (Booking $b) use ($writer, $money, $date) {
                $writer->addRow(Row::fromValuesWithStyles(
                    cellValues: [
                        $b->code, $b->created_at->toDateTimeImmutable(), $b->user->name, $b->user->email, $b->user->phone,
                        $b->showtime->movie->title, $b->showtime->start_time->toDateTimeImmutable(), $b->showtime->room->name,
                        $b->seat_list, $b->seats->count(), $b->combo_list ?: null, $b->subtotal, $b->voucher?->code, $b->discount, $b->total_price,
                        $b->status_label, $b->isConfirmed() || $b->isPaid() ? $b->payment_label : null,
                    ],
                    columnStyles: [1 => $date, 6 => $date, 11 => $money, 13 => $money, 14 => $money],
                ));
            });
    }

    private function writeRevenueSheet(Writer $writer): void
    {
        $sheet = $writer->getCurrentSheet();
        $sheet->setName('Doanh thu theo phim');
        $sheet->setSheetView((new SheetView)->setFreezeRow(2));
        $sheet->setColumnWidth(40, 1);
        $sheet->setColumnWidth(14, 2, 3);
        $sheet->setColumnWidth(18, 4, 5, 6);

        $writer->addRow($this->headerRow(['Phim', 'Số đơn', 'Số vé', 'Tạm tính', 'Giảm giá', 'Doanh thu']));

        // Lọc theo đúng điều kiện đang xem, chỉ tính vé đã xác nhận
        $bookingIds = (clone $this->query)->reorder()->where('status', Booking::STATUS_CONFIRMED)->select('bookings.id');
        $seatCounts = BookingSeat::selectRaw('booking_id, COUNT(*) as seats')->groupBy('booking_id');

        $rows = Booking::query()
            ->whereIn('bookings.id', $bookingIds)
            ->join('showtimes', 'showtimes.id', '=', 'bookings.showtime_id')
            ->join('movies', 'movies.id', '=', 'showtimes.movie_id')
            ->joinSub($seatCounts, 'sc', 'sc.booking_id', '=', 'bookings.id')
            ->groupBy('movies.id', 'movies.title')
            ->selectRaw('movies.title, COUNT(bookings.id) as orders, SUM(sc.seats) as tickets, SUM(bookings.discount) as discount, SUM(bookings.total_price) as revenue')
            ->orderByDesc('revenue')
            ->toBase()
            ->get();

        $money = (new Style)->setFormat(self::MONEY);
        $styles = [3 => $money, 4 => $money, 5 => $money];

        foreach ($rows as $r) {
            $writer->addRow(Row::fromValuesWithStyles(
                cellValues: [$r->title, (int) $r->orders, (int) $r->tickets, (int) $r->revenue + (int) $r->discount, (int) $r->discount, (int) $r->revenue],
                columnStyles: $styles,
            ));
        }

        $total = (new Style)->setFontBold()->setFormat(self::MONEY);
        $writer->addRow(Row::fromValuesWithStyles(
            cellValues: ['TỔNG CỘNG', $rows->sum('orders'), $rows->sum('tickets'), $rows->sum('revenue') + $rows->sum('discount'), $rows->sum('discount'), $rows->sum('revenue')],
            columnStyles: [0 => (new Style)->setFontBold(), 1 => $total, 2 => $total, 3 => $total, 4 => $total, 5 => $total],
        ));
    }

    private function headerRow(array $titles): Row
    {
        return Row::fromValues($titles, (new Style)
            ->setFontBold()
            ->setFontColor('FFFFFF')
            ->setCellAlignment(CellAlignment::CENTER)
            ->setBackgroundColor('E63946'));
    }
}
