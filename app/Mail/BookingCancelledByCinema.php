<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Email báo khách: rạp hủy suất chiếu và/hoặc đã hoàn tiền vé */
class BookingCancelledByCinema extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Booking $booking) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->booking->cancel_reason === Booking::CANCEL_SHOWTIME
            ? "Suất chiếu của vé {$this->booking->code} đã bị hủy"
            : "Vé {$this->booking->code} đã được hoàn tiền");
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.booking-cancelled-by-cinema');
    }
}
