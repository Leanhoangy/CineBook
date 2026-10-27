<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Movie;
use App\Models\Room;
use App\Models\Showtime;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use OpenSpout\Reader\XLSX\Reader;
use Tests\TestCase;

class BookingExportTest extends TestCase
{
    use RefreshDatabase;

    /** Đọc file xlsx trả về thành mảng [tên sheet => các dòng] */
    private function readXlsx(string $path): array
    {
        $reader = new Reader;
        $reader->open($path);
        $sheets = [];
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $sheets[$sheet->getName()][] = $row->toArray();
            }
        }
        $reader->close();

        return $sheets;
    }

    public function test_admin_can_export_filtered_bookings_to_excel(): void
    {
        $movie = Movie::factory()->create(['title' => 'Phim Xuất Excel']);
        $showtime = Showtime::factory()->create([
            'movie_id' => $movie->id,
            'room_id' => Room::factory()->create(['vip_rows' => 0]),
            'price' => 80000,
        ]);
        Booking::factory()->for($showtime)->withSeats(['A1', 'A2'])->create(['code' => 'CBEXCEL1']);
        Booking::factory()->for($showtime)->withSeats(['B1'])->cancelled()->create(['code' => 'CBHUY001']);

        $response = $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.bookings.export', ['status' => 'confirmed']));

        $response->assertOk()->assertDownload();
        $sheets = $this->readXlsx($response->baseResponse->getFile()->getPathname());

        $this->assertSame(['Đơn đặt vé', 'Doanh thu theo phim'], array_keys($sheets));
        $this->assertCount(2, $sheets['Đơn đặt vé']); // tiêu đề + 1 đơn đã xác nhận
        $this->assertSame('CBEXCEL1', $sheets['Đơn đặt vé'][1][0]);
        $this->assertSame(['Phim Xuất Excel', 1, 2, 160000, 0, 160000], $sheets['Doanh thu theo phim'][1]);
    }

    public function test_customer_cannot_export(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.bookings.export'))
            ->assertForbidden();
    }
}
