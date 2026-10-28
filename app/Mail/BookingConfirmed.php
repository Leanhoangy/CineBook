<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Email gửi vé điện tử cho khách sau khi đặt vé thành công */
class BookingConfirmed extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Booking $booking)
    {
        $this->booking->loadMissing(['user', 'showtime.movie', 'showtime.room', 'seats', 'combos', 'voucher']);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Vé {$this->booking->code} – {$this->booking->showtime->movie->title}",
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.booking-confirmed');
    }
}
