<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;

class BookingPolicy
{
    /** Khách chỉ xem được vé của chính mình, admin xem được tất cả */
    public function view(User $user, Booking $booking): bool
    {
        return $user->isAdmin() || $booking->user_id === $user->id;
    }

    public function cancel(User $user, Booking $booking): bool
    {
        return $booking->user_id === $user->id && $booking->canBeCancelledByCustomer();
    }
}
