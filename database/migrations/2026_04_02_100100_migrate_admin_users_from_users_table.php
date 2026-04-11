<?php

use App\Models\AdminUser;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('users') || !Schema::hasTable('admin_users')) {
            return;
        }

        DB::transaction(function () {
            $admins = User::query()
                ->where('access_panel', 'admin')
                ->get([
                    'id',
                    'name',
                    'email',
                    'mobile',
                    'status',
                    'email_verified_at',
                    'mobile_verified_at',
                    'password',
                    'remember_token',
                    'created_at',
                    'updated_at',
                    'deleted_at',
                ]);

            foreach ($admins as $admin) {
                $existingAdmin = AdminUser::query()
                    ->where('legacy_user_id', $admin->id)
                    ->orWhere('email', $admin->email)
                    ->first();

                if ($existingAdmin) {
                    $existingAdmin->fill([
                        'legacy_user_id' => $existingAdmin->legacy_user_id ?: $admin->id,
                        'name' => $admin->name,
                        'mobile' => $admin->mobile,
                        'status' => (bool) $admin->status,
                        'email_verified_at' => $admin->email_verified_at,
                        'mobile_verified_at' => $admin->mobile_verified_at,
                        'password' => $admin->password,
                        'remember_token' => $admin->remember_token,
                        'created_at' => $existingAdmin->created_at ?? $admin->created_at,
                        'updated_at' => $admin->updated_at,
                        'deleted_at' => $admin->deleted_at,
                    ])->save();
                } else {
                    AdminUser::query()->create([
                        'legacy_user_id' => $admin->id,
                        'name' => $admin->name,
                        'email' => $admin->email,
                        'mobile' => $admin->mobile,
                        'status' => (bool) $admin->status,
                        'email_verified_at' => $admin->email_verified_at,
                        'mobile_verified_at' => $admin->mobile_verified_at,
                        'password' => $admin->password,
                        'remember_token' => $admin->remember_token,
                        'created_at' => $admin->created_at,
                        'updated_at' => $admin->updated_at,
                        'deleted_at' => $admin->deleted_at,
                    ]);
                }
            }

            if (Schema::hasTable('roles') && Schema::hasTable('model_has_roles')) {
                $adminRoleIds = DB::table('roles')
                    ->where('guard_name', 'admin')
                    ->pluck('id')
                    ->all();

                if (!empty($adminRoleIds)) {
                    DB::table('model_has_roles')
                        ->whereIn('role_id', $adminRoleIds)
                        ->where('model_type', User::class)
                        ->orderBy('model_id')
                        ->get()
                        ->chunk(500)
                        ->each(function ($rows) {
                            foreach ($rows as $row) {
                                $adminUserId = DB::table('admin_users')
                                    ->where('legacy_user_id', $row->model_id)
                                    ->value('id');

                                if (!$adminUserId) {
                                    continue;
                                }

                                DB::table('model_has_roles')
                                    ->where('role_id', $row->role_id)
                                    ->where('model_type', $row->model_type)
                                    ->where('model_id', $row->model_id)
                                    ->delete();

                                DB::table('model_has_roles')->updateOrInsert(
                                    [
                                        'role_id' => $row->role_id,
                                        'model_type' => AdminUser::class,
                                        'model_id' => $adminUserId,
                                    ],
                                    []
                                );
                            }
                        });
                }
            }

            if (Schema::hasTable('permissions') && Schema::hasTable('model_has_permissions')) {
                $adminPermissionIds = DB::table('permissions')
                    ->where('guard_name', 'admin')
                    ->pluck('id')
                    ->all();

                if (!empty($adminPermissionIds)) {
                    DB::table('model_has_permissions')
                        ->whereIn('permission_id', $adminPermissionIds)
                        ->where('model_type', User::class)
                        ->orderBy('model_id')
                        ->get()
                        ->chunk(500)
                        ->each(function ($rows) {
                            foreach ($rows as $row) {
                                $adminUserId = DB::table('admin_users')
                                    ->where('legacy_user_id', $row->model_id)
                                    ->value('id');

                                if (!$adminUserId) {
                                    continue;
                                }

                                DB::table('model_has_permissions')
                                    ->where('permission_id', $row->permission_id)
                                    ->where('model_type', $row->model_type)
                                    ->where('model_id', $row->model_id)
                                    ->delete();

                                DB::table('model_has_permissions')->updateOrInsert(
                                    [
                                        'permission_id' => $row->permission_id,
                                        'model_type' => AdminUser::class,
                                        'model_id' => $adminUserId,
                                    ],
                                    []
                                );
                            }
                        });
                }
            }
        });
    }

    public function down(): void
    {
        // Keep as no-op to avoid destructive rollback of live role mappings.
    }
};
