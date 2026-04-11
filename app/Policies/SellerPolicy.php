<?php

namespace App\Policies;

use App\Enums\AdminPermissionEnum;
use App\Models\Seller;
use App\Models\AdminUser;
use App\Models\User;
use App\Traits\ChecksPermissions;
use Illuminate\Auth\Access\Response;

class SellerPolicy
{
    use ChecksPermissions;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User|AdminUser $user): bool
    {
        return !($this->hasPermission(AdminPermissionEnum::SELLER_VIEW()) === false);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User|AdminUser $user, Seller $seller): bool
    {
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User|AdminUser $user): bool
    {
        return !($this->hasPermission(AdminPermissionEnum::SELLER_CREATE()) === false);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User|AdminUser $user, Seller $seller): bool
    {
        return !($this->hasPermission(AdminPermissionEnum::SELLER_EDIT()) === false);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User|AdminUser $user, Seller $seller): bool
    {
        return !($this->hasPermission(AdminPermissionEnum::SELLER_DELETE()) === false);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User|AdminUser $user, Seller $seller): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User|AdminUser $user, Seller $seller): bool
    {
        return false;
    }
}
