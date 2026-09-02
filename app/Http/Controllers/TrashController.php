<?php

namespace App\Http\Controllers;

use App\Enums\AdminPermissionEnum;
use App\Traits\ChecksPermissions;
use Illuminate\View\View;

class TrashController extends Controller
{
    use ChecksPermissions;

    public function index(): View
    {
        $canViewCategories = $this->hasPermission(AdminPermissionEnum::CATEGORY_VIEW->value);
        $canViewProducts = $this->hasPermission(AdminPermissionEnum::PRODUCT_VIEW->value);

        abort_unless($canViewCategories || $canViewProducts, 403);

        $canRestoreCategories = $this->hasPermission(AdminPermissionEnum::CATEGORY_DELETE->value);
        $canRestoreProducts = $this->hasPermission(AdminPermissionEnum::PRODUCT_DELETE->value);

        $categoryColumns = [
            ['data' => 'id', 'name' => 'id', 'title' => __('labels.id')],
            ['data' => 'title', 'name' => 'title', 'title' => __('labels.title')],
            ['data' => 'image', 'name' => 'image', 'title' => __('labels.image')],
            ['data' => 'parent', 'name' => 'parent', 'title' => __('labels.parent')],
            ['data' => 'deleted_at', 'name' => 'deleted_at', 'title' => __('labels.deleted_at')],
            ['data' => 'action', 'name' => 'action', 'title' => __('labels.action'), 'orderable' => false, 'searchable' => false],
        ];

        $productColumns = [
            ['data' => 'id', 'name' => 'id', 'title' => __('labels.id')],
            ['data' => 'title', 'name' => 'title', 'title' => __('labels.title')],
            ['data' => 'image', 'name' => 'image', 'title' => __('labels.image')],
            ['data' => 'category', 'name' => 'category', 'title' => __('labels.category')],
            ['data' => 'deleted_at', 'name' => 'deleted_at', 'title' => __('labels.deleted_at')],
            ['data' => 'action', 'name' => 'action', 'title' => __('labels.action'), 'orderable' => false, 'searchable' => false],
        ];

        return view('admin.trash.index', compact(
            'canViewCategories',
            'canViewProducts',
            'canRestoreCategories',
            'canRestoreProducts',
            'categoryColumns',
            'productColumns'
        ));
    }
}
