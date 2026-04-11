<?php

namespace App\Policies;

use App\Models\SellerFeedback;
use App\Models\AdminUser;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SellerFeedbackPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User|AdminUser $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User|AdminUser $user, SellerFeedback $sellerFeedback): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User|AdminUser $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User|AdminUser $user, SellerFeedback $sellerFeedback): bool
    {
        return $user->id === $sellerFeedback->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User|AdminUser $user, SellerFeedback $sellerFeedback): bool
    {
        return $user->id === $sellerFeedback->user_id;
    }
}
