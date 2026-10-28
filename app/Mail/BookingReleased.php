<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Email báo vé đã tự hủy vì quá hạn thanh toán */
class BookingReleased extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Booking $booking) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Vé {$this->booking->code} đã bị hủy do chưa thanh toán");
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.booking-released');
    }
}
