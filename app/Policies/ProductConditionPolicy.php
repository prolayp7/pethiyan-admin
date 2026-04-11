<?php

namespace App\Policies;

use App\Enums\DefaultSystemRolesEnum;
use App\Enums\SellerPermissionEnum;
use App\Models\ProductCondition;
use App\Models\AdminUser;
use App\Models\User;
use App\Traits\ChecksPermissions;

class ProductConditionPolicy
{
    use ChecksPermissions;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User|AdminUser $user): bool
    {
        if ($user->hasRole(DefaultSystemRolesEnum::SELLER())) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User|AdminUser $user, ProductCondition $productCondition): bool
    {
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User|AdminUser $user): bool
    {
        if ($user->hasRole(DefaultSystemRolesEnum::SELLER())) {
            return true;
        }

        return $this->hasPermission(SellerPermissionEnum::PRODUCT_CONDITION_CREATE());
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User|AdminUser $user, ProductCondition $productCondition): bool
    {
        if ($user->hasRole(DefaultSystemRolesEnum::SELLER())) {
            return true;
        }

        return $this->hasPermission(SellerPermissionEnum::PRODUCT_CONDITION_EDIT());
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User|AdminUser $user, ProductCondition $productCondition): bool
    {
        if ($user->hasRole(DefaultSystemRolesEnum::SELLER())) {
            return true;
        }

        return $this->hasPermission(SellerPermissionEnum::PRODUCT_CONDITION_DELETE());
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User|AdminUser $user, ProductCondition $productCondition): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User|AdminUser $user, ProductCondition $productCondition): bool
    {
        return false;
    }
}
