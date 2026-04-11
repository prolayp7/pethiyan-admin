<?php

namespace App\Policies;

use App\Enums\AdminPermissionEnum;
use App\Enums\DefaultSystemRolesEnum;
use App\Enums\SellerPermissionEnum;
use App\Models\Notification;
use App\Traits\ChecksPermissions;

class NotificationPolicy
{
    use ChecksPermissions;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny($user): bool
    {
        try {
            if ($this->getPanel() == 'seller') {
                // Only the seller who owns the product can update it
                if ($user->seller() === null) {
                    return false;
                }
                // Check role or permission
                if (
                    $user->hasRole(DefaultSystemRolesEnum::SELLER()) ||
                    $this->hasPermission(SellerPermissionEnum::NOTIFICATION_VIEW())
                ) {
                    return true;
                }
            }
            return $this->hasPermission(AdminPermissionEnum::NOTIFICATION_VIEW());

        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view($user, Notification $notification): bool
    {
        try {
            if ($this->getPanel() == 'seller') {
                // Only the seller who owns the product can update it
                if ($user->seller() === null) {
                    return false;
                }
                // Check if the user is the owner
                if ($user->seller()->user_id === $notification->user_id) {
                    // Check role or permission
                    if (
                        $user->hasRole(DefaultSystemRolesEnum::SELLER()) ||
                        $this->hasPermission(SellerPermissionEnum::NOTIFICATION_VIEW())
                    ) {
                        return true;
                    }
                }
            }
            return $this->hasPermission(AdminPermissionEnum::NOTIFICATION_VIEW());

        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Determine whether the user can create models.
     */
    public function create($user): bool
    {
        try {
            return $this->hasPermission(AdminPermissionEnum::NOTIFICATION_CREATE());
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update($user, Notification $notification): bool
    {
        return $this->extracted($user, $notification);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function markAsRead($user, Notification $notification): bool
    {
        return $this->extracted($user, $notification);
    }

    public function readAll($user): bool
    {
        try {
            if ($this->getPanel() == 'seller') {
                // Only the seller who owns the product can update it
                if ($user->seller() === null) {
                    return false;
                }
                // Check role or permission
                if (
                    $user->hasRole(DefaultSystemRolesEnum::SELLER()) ||
                    $this->hasPermission(SellerPermissionEnum::NOTIFICATION_EDIT())
                ) {
                    return true;
                }
            }
            return $this->hasPermission(AdminPermissionEnum::NOTIFICATION_EDIT());

        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete($user, Notification $notification): bool
    {
        try {
            if ($this->getPanel() == 'seller') {
                // Only the seller who owns the product can update it
                if ($user->seller() === null) {
                    return false;
                }
                // Check if the user is the owner
                if ($user->seller()->user_id === $notification->user_id) {
                    // Check role or permission
                    if (
                        $user->hasRole(DefaultSystemRolesEnum::SELLER()) ||
                        $this->hasPermission(SellerPermissionEnum::NOTIFICATION_DELETE())
                    ) {
                        return true;
                    }
                }
            }
            return $this->hasPermission(AdminPermissionEnum::NOTIFICATION_DELETE());

        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore($user, Notification $notification): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete($user, Notification $notification): bool
    {
        return false;
    }

    /**
     * @param mixed $user
     * @param Notification $notification
     * @return bool
     */
    public function extracted($user, Notification $notification): bool
    {
        try {
            if ($this->getPanel() == 'seller') {
                // Only the seller who owns the product can update it
                if ($user->seller() === null) {
                    return false;
                }
                // Check if the user is the owner
                if ($user->seller()->user_id === $notification->user_id) {
                    // Check role or permission
                    if (
                        $user->hasRole(DefaultSystemRolesEnum::SELLER()) ||
                        $this->hasPermission(SellerPermissionEnum::NOTIFICATION_EDIT())
                    ) {
                        return true;
                    }
                }
            }
            return $this->hasPermission(AdminPermissionEnum::NOTIFICATION_EDIT());

        } catch (\Exception) {
            return false;
        }
    }
}
