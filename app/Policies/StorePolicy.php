<?php

namespace App\Policies;

use App\Enums\AdminPermissionEnum;
use App\Enums\DefaultSystemRolesEnum;
use App\Enums\GuardNameEnum;
use App\Enums\SellerPermissionEnum;
use App\Models\Store;
use App\Models\AdminUser;
use App\Models\User;
use App\Traits\ChecksPermissions;

class StorePolicy
{
    use ChecksPermissions;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User|AdminUser $user): bool
    {
        // Seller panel: sellers can access their stores list if they are sellers
        if ($user->hasRole(DefaultSystemRolesEnum::SELLER())) {
            return true;
        }
        // Admin panel: require explicit admin permission
        if ($this->hasPermission(AdminPermissionEnum::STORE_VIEW())) {
            return true;
        }
        // Fallback to seller permission (for legacy guards)
        return $this->hasPermission(SellerPermissionEnum::STORE_VIEW());
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User|AdminUser $user, Store $store): bool
    {
        try {
            // Admin with store.view can view any store
            if ($this->hasPermission(AdminPermissionEnum::STORE_VIEW())) {
                return true;
            }

            // Seller must own the store and have seller view permission or role
            if ($user->seller() !== null && $user->seller()->id === $store->seller_id) {
                if (
                    $user->hasRole(DefaultSystemRolesEnum::SELLER()) ||
                    $this->hasPermission(SellerPermissionEnum::STORE_VIEW())
                ) {
                    return true;
                }
            }

            return false;
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User|AdminUser $user): bool
    {
        try {
            // Admin can create stores (always for Pethiyan seller)
            if ($user->seller() === null) {
                return $this->hasPermission(AdminPermissionEnum::STORE_CREATE());
            }

            // Must have a seller role or explicit permission
            if (
                $user->hasRole(DefaultSystemRolesEnum::SELLER()) ||
                $this->hasPermission(SellerPermissionEnum::STORE_CREATE())
            ) {
                return true;
            }

            return false;
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User|AdminUser $user, Store $store): bool
    {
        try {
            // Admin can update any store
            if ($user->seller() === null) {
                return $this->hasPermission(AdminPermissionEnum::STORE_EDIT());
            }

            // Seller must own the store
            if ($user->seller()->id === $store->seller_id) {
                if (
                    $user->hasRole(DefaultSystemRolesEnum::SELLER()) ||
                    $this->hasPermission(SellerPermissionEnum::STORE_EDIT())
                ) {
                    return true;
                }
            }

            return false;

        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User|AdminUser $user, Store $store): bool
    {
        try {
            // Admin can delete any store
            if ($user->seller() === null) {
                return $this->hasPermission(AdminPermissionEnum::STORE_DELETE());
            }

            // Seller must own the store
            if ($user->seller()->id === $store->seller_id) {
                if (
                    $user->hasRole(DefaultSystemRolesEnum::SELLER()) ||
                    $this->hasPermission(SellerPermissionEnum::STORE_DELETE())
                ) {
                    return true;
                }
            }

            return false;

        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User|AdminUser $user, Store $store): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User|AdminUser $user, Store $store): bool
    {
        return false;
    }

    public function verifyStore(User|AdminUser $user): bool
    {
        // Only admins with explicit permission can verify
        return $this->hasPermission(AdminPermissionEnum::STORE_VERIFY());
    }
}
