<?php

namespace App\Policies;

use App\Enums\AdminPermissionEnum;
use App\Enums\DefaultSystemRolesEnum;
use App\Enums\SellerPermissionEnum;
use App\Models\Product;
use App\Models\AdminUser;
use App\Models\User;
use App\Traits\ChecksPermissions;
use Illuminate\Auth\Access\Response;

class ProductPolicy
{
    use ChecksPermissions;

    public function before(User|AdminUser $user, string $ability): ?bool
    {
        if ($user->hasRole(DefaultSystemRolesEnum::SUPER_ADMIN())) {
            return true;
        }

        return null;
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User|AdminUser $user): bool
    {
        try {
            if ($user->seller() === null) {
                return $this->hasPermission(AdminPermissionEnum::PRODUCT_VIEW());
            }

            return $user->hasRole(DefaultSystemRolesEnum::SELLER())
                || $this->hasPermission(SellerPermissionEnum::PRODUCT_VIEW());
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User|AdminUser $user, Product $product): bool
    {
        try {
            // Only the seller who owns the product can update it
            if ($user->seller() === null) {
                return $this->hasPermission(AdminPermissionEnum::PRODUCT_VIEW());
            }

            // Check if the user is the owner
            if ($user->seller()->id === $product->seller_id) {
                // Check role or permission
                if (
                    $user->hasRole(DefaultSystemRolesEnum::SELLER()) ||
                    $this->hasPermission(SellerPermissionEnum::PRODUCT_VIEW())
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
            // Admin users can create products with the PRODUCT_CREATE permission
            if ($user->seller() === null) {
                return $this->hasPermission(AdminPermissionEnum::PRODUCT_CREATE());
            }

            // Must have a seller role or explicit permission
            if (
                $user->hasRole(DefaultSystemRolesEnum::SELLER()) ||
                $this->hasPermission(SellerPermissionEnum::PRODUCT_CREATE())
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
    public function update(User|AdminUser $user, Product $product): bool
    {
        try {
            if ($user->seller() === null) {
                return $this->hasPermission(AdminPermissionEnum::PRODUCT_EDIT());
            }

            // Check if the user is the owner
            if ($user->seller()->id === $product->seller_id) {
                // Check role or permission
                if (
                    $user->hasRole(DefaultSystemRolesEnum::SELLER()) ||
                    $this->hasPermission(SellerPermissionEnum::PRODUCT_EDIT())
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
    public function delete(User|AdminUser $user, Product $product): bool
    {
        try {
            if ($user->seller() === null) {
                return $this->hasPermission(AdminPermissionEnum::PRODUCT_DELETE());
            }

            // Check if the user is the owner
            if ($user->seller()->id === $product->seller_id) {
                // Check role or permission
                if (
                    $user->hasRole(DefaultSystemRolesEnum::SELLER()) ||
                    $this->hasPermission(SellerPermissionEnum::PRODUCT_DELETE())
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
    public function restore(User|AdminUser $user, Product $product): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User|AdminUser $user, Product $product): bool
    {
        return false;
    }

    public function verifyProduct(User|AdminUser $user, Product $product): bool
    {
        try {
            return $this->hasPermission(AdminPermissionEnum::PRODUCT_STATUS_UPDATE());
        } catch (\Exception) {
            return false;
        }
    }
}
