<?php

namespace App\Policies;

use App\Models\Review;
use App\Models\User;

class ReviewPolicy
{
    /** Khách xóa được đánh giá của chính mình, admin xóa được tất cả */
    public function delete(User $user, Review $review): bool
    {
        return $user->isAdmin() || $review->user_id === $user->id;
    }
}
