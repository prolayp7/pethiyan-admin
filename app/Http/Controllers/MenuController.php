<?php

namespace App\Http\Controllers;

use App\Enums\Menu\MenuItemTypeEnum;
use App\Http\Requests\MegaMenu\StoreColumnRequest;
use App\Http\Requests\MegaMenu\StoreLinkRequest;
use App\Http\Requests\MegaMenu\StorePanelRequest;
use App\Http\Requests\MegaMenu\UpdateColumnRequest;
use App\Http\Requests\MegaMenu\UpdateLinkRequest;
use App\Http\Requests\MegaMenu\UpdatePanelRequest;
use App\Http\Requests\Menu\StoreMenuItemRequest;
use App\Http\Requests\Menu\StoreMenuRequest;
use App\Http\Requests\Menu\UpdateMenuItemRequest;
use App\Http\Requests\Menu\UpdateMenuRequest;
use App\Models\MegaMenuColumn;
use App\Models\MegaMenuLink;
use App\Models\MegaMenuPanel;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Traits\ChecksPermissions;
use App\Traits\PanelAware;
use App\Types\Api\ApiResponseType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class MenuController extends Controller
{
    use ChecksPermissions, PanelAware;

    /* ═══════════════════════════════════════════════════════════════
     |  MENUS
     ═══════════════════════════════════════════════════════════════ */

    public function index(): View
    {
        $columns = [
            ['data' => 'id',         'name' => 'id',         'title' => 'ID'],
            ['data' => 'name',       'name' => 'name',       'title' => 'Name'],
            ['data' => 'slug',       'name' => 'slug',       'title' => 'Slug'],
            ['data' => 'location',   'name' => 'location',   'title' => 'Location', 'orderable' => false, 'searchable' => false],
            ['data' => 'items_count','name' => 'items_count','title' => 'Items',    'orderable' => false, 'searchable' => false],
            ['data' => 'is_active',  'name' => 'is_active',  'title' => 'Status',   'orderable' => false, 'searchable' => false],
            ['data' => 'created_at', 'name' => 'created_at', 'title' => 'Created'],
            ['data' => 'action',     'name' => 'action',     'title' => 'Action',   'orderable' => false, 'searchable' => false],
        ];

        return view('admin.menus.index', compact('columns'));
    }

    public function store(StoreMenuRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            if (empty($data['slug'])) {
                $data['slug'] = Str::slug($data['name']);
            }
            $data['location']  = $data['location'] ?? 'header';
            $data['is_active'] = $request->boolean('is_active', true);
            $menu = Menu::create($data);

            return ApiResponseType::sendJsonResponse(true, 'Menu created successfully.', $menu);
        } catch (\Throwable $e) {
            return ApiResponseType::sendJsonResponse(false, $e->getMessage(), [], 500);
        }
    }

    public function show($id): JsonResponse
    {
        try {
            $menu = Menu::findOrFail($id);
            return ApiResponseType::sendJsonResponse(true, 'Menu retrieved successfully.', $menu);
        } catch (\Throwable $e) {
            return ApiResponseType::sendJsonResponse(false, $e->getMessage(), [], 404);
        }
    }

    public function update(UpdateMenuRequest $request, $id): JsonResponse
    {
        try {
            $menu = Menu::findOrFail($id);
            $data = $request->validated();
            $data['is_active'] = $request->boolean('is_active', $menu->is_active);
            $menu->update($data);

            return ApiResponseType::sendJsonResponse(true, 'Menu updated successfully.', $menu->fresh());
        } catch (\Throwable $e) {
            return ApiResponseType::sendJsonResponse(false, $e->getMessage(), [], 500);
        }
    }

    public function destroy($id): JsonResponse
    {
        try {
            $menu = Menu::findOrFail($id);
            $menu->delete();
            return ApiResponseType::sendJsonResponse(true, 'Menu deleted successfully.');
        } catch (\Throwable $e) {
            return ApiResponseType::sendJsonResponse(false, $e->getMessage(), [], 500);
        }
    }

    public function toggleActive($id): JsonResponse
    {
        try {
            $menu = Menu::findOrFail($id);
            $menu->update(['is_active' => !$menu->is_active]);
            return ApiResponseType::sendJsonResponse(true, 'Menu status updated.', ['is_active' => $menu->is_active]);
        } catch (\Throwable $e) {
            return ApiResponseType::sendJsonResponse(false, $e->getMessage(), [], 500);
        }
    }

    public function datatable(Request $request): JsonResponse
    {
        $draw   = $request->get('draw');
        $start  = (int) $request->get('start', 0);
        $length = (int) $request->get('length', 10);
        $search = $request->get('search')['value'] ?? '';

        $orderColIdx  = $request->get('order')[0]['column'] ?? 0;
        $orderDir     = $request->get('order')[0]['dir']    ?? 'desc';
        $cols         = ['id', 'name', 'slug', 'location', 'items_count', 'is_active', 'created_at'];
        $orderColumn  = in_array($orderColIdx, array_keys($cols)) ? $cols[$orderColIdx] : 'id';

        $query = Menu::withCount('allItems');

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        $total    = Menu::count();
        $filtered = $query->count();

        $data = $query->orderBy($orderColumn === 'items_count' ? 'id' : $orderColumn, $orderDir)
            ->skip($start)->take($length)->get()
            ->map(function ($menu) {
                $locationBadge = $menu->location === 'footer'
                    ? '<span class="badge bg-teal-lt">Footer</span>'
                    : '<span class="badge bg-indigo-lt">Header</span>';
                return [
                    'id'          => $menu->id,
                    'name'        => e($menu->name),
                    'slug'        => '<code>' . e($menu->slug) . '</code>',
                    'location'    => $locationBadge,
                    'items_count' => '<span class="badge bg-blue-lt">' . $menu->all_items_count . ' items</span>',
                    'is_active'   => $this->renderStatusToggle($menu),
                    'created_at'  => $menu->created_at->format('Y-m-d'),
                    'action'      => $this->renderMenuActions($menu),
                ];
            });

        return response()->json([
            'draw'            => (int) $draw,
            'recordsTotal'    => $total,
            'recordsFiltered' => $filtered,
            'data'            => $data,
        ]);
    }

    /* ═══════════════════════════════════════════════════════════════
     |  MENU ITEMS
     ═══════════════════════════════════════════════════════════════ */

    public function itemsIndex($menuId): View
    {
        $menu = Menu::findOrFail($menuId);

        $columns = [
            ['data' => 'sort_order', 'name' => 'sort_order', 'title' => '#'],
            ['data' => 'label',      'name' => 'label',      'title' => 'Label'],
            ['data' => 'href',       'name' => 'href',       'title' => 'URL'],
            ['data' => 'type',       'name' => 'type',       'title' => 'Type',     'orderable' => false, 'searchable' => false],
            ['data' => 'parent',     'name' => 'parent',     'title' => 'Parent',   'orderable' => false, 'searchable' => false],
            ['data' => 'is_active',  'name' => 'is_active',  'title' => 'Status',   'orderable' => false, 'searchable' => false],
            ['data' => 'action',     'name' => 'action',     'title' => 'Action',   'orderable' => false, 'searchable' => false],
        ];

        $types        = MenuItemTypeEnum::cases();
        $parentItems  = MenuItem::where('menu_id', $menuId)->whereNull('parent_id')->orderBy('sort_order')->get();

        return view('admin.menus.items', compact('menu', 'columns', 'types', 'parentItems'));
    }

    public function itemsDatatable(Request $request, $menuId): JsonResponse
    {
        Menu::findOrFail($menuId);

        $draw   = $request->get('draw');
        $start  = (int) $request->get('start', 0);
        $length = (int) $request->get('length', 25);
        $search = $request->get('search')['value'] ?? '';

        $query = MenuItem::with('parent')->where('menu_id', $menuId);

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('label', 'like', "%{$search}%")
                  ->orWhere('href',  'like', "%{$search}%");
            });
        }

        $total    = MenuItem::where('menu_id', $menuId)->count();
        $filtered = $query->count();

        $data = $query->orderBy('sort_order')->skip($start)->take($length)->get()
            ->map(function ($item) use ($menuId) {
                $typeEnum = $item->type instanceof MenuItemTypeEnum ? $item->type : MenuItemTypeEnum::from($item->type->value ?? $item->type);
                return [
                    'sort_order' => $item->sort_order,
                    'label'      => ($item->parent_id ? '&nbsp;&nbsp;&nbsp;↳ ' : '') . e($item->label),
                    'href'       => $item->href ? '<code class="text-muted small">' . e($item->href) . '</code>' : '—',
                    'type'       => '<span class="badge text-uppercase ' . $typeEnum->badgeClass() . '">' . $typeEnum->label() . '</span>',
                    'parent'     => $item->parent ? e($item->parent->label) : '—',
                    'is_active'  => $this->renderItemStatusToggle($item, $menuId),
                    'action'     => $this->renderItemActions($item, $menuId),
                ];
            });

        return response()->json([
            'draw'            => (int) $draw,
            'recordsTotal'    => $total,
            'recordsFiltered' => $filtered,
            'data'            => $data,
        ]);
    }

    public function storeItem(StoreMenuItemRequest $request, $menuId): JsonResponse
    {
        try {
            Menu::findOrFail($menuId);
            $data              = $request->validated();
            $data['menu_id']   = $menuId;
            $data['is_active'] = $request->boolean('is_active', true);
            $data['target']    = $data['target'] ?? '_self';
            if (empty($data['sort_order'])) {
                $data['sort_order'] = MenuItem::where('menu_id', $menuId)->max('sort_order') + 1;
            }
            $item = MenuItem::create($data);

            return ApiResponseType::sendJsonResponse(true, 'Menu item created successfully.', $item);
        } catch (\Throwable $e) {
            return ApiResponseType::sendJsonResponse(false, $e->getMessage(), [], 500);
        }
    }

    public function showItem($menuId, $id): JsonResponse
    {
        try {
            $item = MenuItem::where('menu_id', $menuId)->findOrFail($id);
            return ApiResponseType::sendJsonResponse(true, 'Menu item retrieved.', $item);
        } catch (\Throwable $e) {
            return ApiResponseType::sendJsonResponse(false, $e->getMessage(), [], 404);
        }
    }

    public function updateItem(UpdateMenuItemRequest $request, $menuId, $id): JsonResponse
    {
        try {
            $item = MenuItem::where('menu_id', $menuId)->findOrFail($id);
            $data = $request->validated();
            $data['is_active'] = $request->boolean('is_active', $item->is_active);
            $item->update($data);

            return ApiResponseType::sendJsonResponse(true, 'Menu item updated successfully.', $item->fresh());
        } catch (\Throwable $e) {
            return ApiResponseType::sendJsonResponse(false, $e->getMessage(), [], 500);
        }
    }

    public function destroyItem($menuId, $id): JsonResponse
    {
        try {
            $item = MenuItem::where('menu_id', $menuId)->findOrFail($id);
            $item->children()->delete();
            $item->delete();
            return ApiResponseType::sendJsonResponse(true, 'Menu item deleted successfully.');
        } catch (\Throwable $e) {
            return ApiResponseType::sendJsonResponse(false, $e->getMessage(), [], 500);
        }
    }

    public function toggleItemActive($menuId, $id): JsonResponse
    {
        try {
            $item = MenuItem::where('menu_id', $menuId)->findOrFail($id);
            $item->update(['is_active' => !$item->is_active]);
            return ApiResponseType::sendJsonResponse(true, 'Item status updated.', ['is_active' => $item->is_active]);
        } catch (\Throwable $e) {
            return ApiResponseType::sendJsonResponse(false, $e->getMessage(), [], 500);
        }
    }

    /* ═══════════════════════════════════════════════════════════════
     |  MEGA MENU BUILDER
     ═══════════════════════════════════════════════════════════════ */

    public function megaMenuIndex($menuId, $itemId): View
    {
        $menu     = Menu::findOrFail($menuId);
        $menuItem = MenuItem::where('menu_id', $menuId)->findOrFail($itemId);
        $panels   = MegaMenuPanel::with('columns.links')
            ->where('menu_item_id', $itemId)
            ->orderBy('sort_order')
            ->get();

        return view('admin.menus.mega-menu', compact('menu', 'menuItem', 'panels'));
    }

    /* ── Panels ── */

    public function storePanel(StorePanelRequest $request, $menuId, $itemId): JsonResponse
    {
        try {
            MenuItem::where('menu_id', $menuId)->findOrFail($itemId);
            $data = $request->validated();
            unset($data['panel_image']);
            $data['menu_item_id'] = $itemId;
            $data['is_active']    = $request->boolean('is_active', true);
            $data['image_path']   = $this->storeMegaMenuPanelImage($request) ?? ($data['image_path'] ?? null);
            if (empty($data['sort_order'])) {
                $data['sort_order'] = MegaMenuPanel::where('menu_item_id', $itemId)->max('sort_order') + 1;
            }
            $panel = MegaMenuPanel::create($data);
            return ApiResponseType::sendJsonResponse(true, 'Panel created successfully.', $panel);
        } catch (\Throwable $e) {
            return ApiResponseType::sendJsonResponse(false, $e->getMessage(), [], 500);
        }
    }

    public function showPanel($menuId, $itemId, $panelId): JsonResponse
    {
        try {
            $panel = MegaMenuPanel::where('menu_item_id', $itemId)->findOrFail($panelId);
            return ApiResponseType::sendJsonResponse(true, 'Panel retrieved.', $panel);
        } catch (\Throwable $e) {
            return ApiResponseType::sendJsonResponse(false, $e->getMessage(), [], 404);
        }
    }

    public function updatePanel(UpdatePanelRequest $request, $menuId, $itemId, $panelId): JsonResponse
    {
        try {
            $panel = MegaMenuPanel::where('menu_item_id', $itemId)->findOrFail($panelId);
            $data  = $request->validated();
            unset($data['panel_image']);
            $data['is_active'] = $request->boolean('is_active', $panel->is_active);
            $uploadedImagePath = $this->storeMegaMenuPanelImage($request);
            if ($uploadedImagePath) {
                $this->deleteMegaMenuPanelImage($panel->image_path);
                $data['image_path'] = $uploadedImagePath;
            }
            $panel->update($data);
            return ApiResponseType::sendJsonResponse(true, 'Panel updated successfully.', $panel->fresh());
        } catch (\Throwable $e) {
            return ApiResponseType::sendJsonResponse(false, $e->getMessage(), [], 500);
        }
    }

    private function storeMegaMenuPanelImage(Request $request): ?string
    {
        if (!$request->hasFile('panel_image')) {
            return null;
        }

        $path = $request->file('panel_image')->store('mega-menu/panels', 'public');

        return '/storage/' . ltrim($path, '/');
    }

    private function deleteMegaMenuPanelImage(?string $path): void
    {
        if (!$path || !str_starts_with($path, '/storage/')) {
            return;
        }

        $relativePath = ltrim(Str::after($path, '/storage/'), '/');

        if ($relativePath !== '') {
            Storage::disk('public')->delete($relativePath);
        }
    }

    public function destroyPanel($menuId, $itemId, $panelId): JsonResponse
    {
        try {
            $panel = MegaMenuPanel::where('menu_item_id', $itemId)->findOrFail($panelId);
            foreach ($panel->columns as $col) {
                $col->links()->delete();
            }
            $panel->columns()->delete();
            $panel->delete();
            return ApiResponseType::sendJsonResponse(true, 'Panel deleted successfully.');
        } catch (\Throwable $e) {
            return ApiResponseType::sendJsonResponse(false, $e->getMessage(), [], 500);
        }
    }

    public function togglePanelActive($menuId, $itemId, $panelId): JsonResponse
    {
        try {
            $panel = MegaMenuPanel::where('menu_item_id', $itemId)->findOrFail($panelId);
            $panel->update(['is_active' => !$panel->is_active]);
            return ApiResponseType::sendJsonResponse(true, 'Panel status updated.', ['is_active' => $panel->is_active]);
        } catch (\Throwable $e) {
            return ApiResponseType::sendJsonResponse(false, $e->getMessage(), [], 500);
        }
    }

    /* ── Columns ── */

    public function storeColumn(StoreColumnRequest $request, $menuId, $itemId, $panelId): JsonResponse
    {
        try {
            MegaMenuPanel::where('menu_item_id', $itemId)->findOrFail($panelId);
            $data = $request->validated();
            $data['panel_id'] = $panelId;
            if (empty($data['sort_order'])) {
                $data['sort_order'] = MegaMenuColumn::where('panel_id', $panelId)->max('sort_order') + 1;
            }
            $col = MegaMenuColumn::create($data);
            return ApiResponseType::sendJsonResponse(true, 'Column created successfully.', $col);
        } catch (\Throwable $e) {
            return ApiResponseType::sendJsonResponse(false, $e->getMessage(), [], 500);
        }
    }

    public function showColumn($menuId, $itemId, $panelId, $columnId): JsonResponse
    {
        try {
            $col = MegaMenuColumn::where('panel_id', $panelId)->findOrFail($columnId);
            return ApiResponseType::sendJsonResponse(true, 'Column retrieved.', $col);
        } catch (\Throwable $e) {
            return ApiResponseType::sendJsonResponse(false, $e->getMessage(), [], 404);
        }
    }

    public function updateColumn(UpdateColumnRequest $request, $menuId, $itemId, $panelId, $columnId): JsonResponse
    {
        try {
            $col = MegaMenuColumn::where('panel_id', $panelId)->findOrFail($columnId);
            $col->update($request->validated());
            return ApiResponseType::sendJsonResponse(true, 'Column updated successfully.', $col->fresh());
        } catch (\Throwable $e) {
            return ApiResponseType::sendJsonResponse(false, $e->getMessage(), [], 500);
        }
    }

    public function destroyColumn($menuId, $itemId, $panelId, $columnId): JsonResponse
    {
        try {
            $col = MegaMenuColumn::where('panel_id', $panelId)->findOrFail($columnId);
            $col->links()->delete();
            $col->delete();
            return ApiResponseType::sendJsonResponse(true, 'Column deleted successfully.');
        } catch (\Throwable $e) {
            return ApiResponseType::sendJsonResponse(false, $e->getMessage(), [], 500);
        }
    }

    /* ── Links ── */

    public function storeLink(StoreLinkRequest $request, $menuId, $itemId, $panelId, $columnId): JsonResponse
    {
        try {
            MegaMenuColumn::where('panel_id', $panelId)->findOrFail($columnId);
            $data = $request->validated();
            $data['column_id']  = $columnId;
            $data['is_active']  = $request->boolean('is_active', true);
            $data['target']     = $data['target'] ?? '_self';
            if (empty($data['sort_order'])) {
                $data['sort_order'] = MegaMenuLink::where('column_id', $columnId)->max('sort_order') + 1;
            }
            $link = MegaMenuLink::create($data);
            return ApiResponseType::sendJsonResponse(true, 'Link created successfully.', $link);
        } catch (\Throwable $e) {
            return ApiResponseType::sendJsonResponse(false, $e->getMessage(), [], 500);
        }
    }

    public function showLink($menuId, $itemId, $panelId, $columnId, $linkId): JsonResponse
    {
        try {
            $link = MegaMenuLink::where('column_id', $columnId)->findOrFail($linkId);
            return ApiResponseType::sendJsonResponse(true, 'Link retrieved.', $link);
        } catch (\Throwable $e) {
            return ApiResponseType::sendJsonResponse(false, $e->getMessage(), [], 404);
        }
    }

    public function updateLink(UpdateLinkRequest $request, $menuId, $itemId, $panelId, $columnId, $linkId): JsonResponse
    {
        try {
            $link = MegaMenuLink::where('column_id', $columnId)->findOrFail($linkId);
            $data = $request->validated();
            $data['is_active'] = $request->boolean('is_active', $link->is_active);
            $link->update($data);
            return ApiResponseType::sendJsonResponse(true, 'Link updated successfully.', $link->fresh());
        } catch (\Throwable $e) {
            return ApiResponseType::sendJsonResponse(false, $e->getMessage(), [], 500);
        }
    }

    public function destroyLink($menuId, $itemId, $panelId, $columnId, $linkId): JsonResponse
    {
        try {
            $link = MegaMenuLink::where('column_id', $columnId)->findOrFail($linkId);
            $link->delete();
            return ApiResponseType::sendJsonResponse(true, 'Link deleted successfully.');
        } catch (\Throwable $e) {
            return ApiResponseType::sendJsonResponse(false, $e->getMessage(), [], 500);
        }
    }

    /* ═══════════════════════════════════════════════════════════════
     |  RENDER HELPERS (HTML snippets for DataTable cells)
     ═══════════════════════════════════════════════════════════════ */

    private function renderStatusToggle(Menu $menu): string
    {
        $checked = $menu->is_active ? 'checked' : '';
        $url     = route('admin.menus.toggle-active', $menu->id);
        return <<<HTML
<div class="form-check form-switch mb-0">
  <input class="form-check-input menu-toggle-active" type="checkbox" {$checked}
         data-id="{$menu->id}" data-url="{$url}">
</div>
HTML;
    }

    private function renderMenuActions(Menu $menu): string
    {
        $itemsUrl    = route('admin.menus.items.index', $menu->id);
        $editUrl     = route('admin.menus.show', $menu->id);
        $deleteUrl   = route('admin.menus.destroy', $menu->id);
        return <<<HTML
<div class="d-flex gap-1 justify-content-center">
  <a href="{$itemsUrl}" class="btn btn-sm btn-outline-primary" title="Manage Items">
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
      <line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/>
      <line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/>
      <line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/>
    </svg> Items
  </a>
  <button type="button" class="btn btn-sm btn-outline-secondary menu-edit-btn" data-id="{$menu->id}" data-url="{$editUrl}" title="Edit">
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
      <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
      <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
    </svg>
  </button>
  <button type="button" class="btn btn-sm btn-outline-danger menu-delete-btn" data-id="{$menu->id}" data-url="{$deleteUrl}" title="Delete">
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
      <polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/>
      <path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/>
    </svg>
  </button>
</div>
HTML;
    }

    private function renderItemStatusToggle(MenuItem $item, $menuId): string
    {
        $checked = $item->is_active ? 'checked' : '';
        $url     = route('admin.menus.items.toggle-active', [$menuId, $item->id]);
        return <<<HTML
<div class="form-check form-switch mb-0">
  <input class="form-check-input item-toggle-active" type="checkbox" {$checked}
         data-id="{$item->id}" data-url="{$url}">
</div>
HTML;
    }

    private function renderItemActions(MenuItem $item, $menuId): string
    {
        $editUrl   = route('admin.menus.items.show',    [$menuId, $item->id]);
        $deleteUrl = route('admin.menus.items.destroy',  [$menuId, $item->id]);
        $typeEnum  = $item->type instanceof MenuItemTypeEnum ? $item->type : MenuItemTypeEnum::from($item->type->value ?? $item->type);
        $megaBtn   = '';
        if ($typeEnum === MenuItemTypeEnum::MEGA_MENU) {
            $megaUrl = route('admin.menus.items.mega-menu.index', [$menuId, $item->id]);
            $megaBtn = <<<HTML
  <a href="{$megaUrl}" class="btn btn-sm btn-outline-warning" title="Mega Menu Builder">
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
      <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/>
      <rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>
    </svg>
  </a>
HTML;
        }
        return <<<HTML
<div class="d-flex gap-1 justify-content-center">
  {$megaBtn}
  <button class="btn btn-sm btn-outline-secondary item-edit-btn"
          data-id="{$item->id}" data-url="{$editUrl}" data-menu-id="{$menuId}" title="Edit">
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
      <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
      <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
    </svg>
  </button>
  <button class="btn btn-sm btn-outline-danger item-delete-btn"
          data-id="{$item->id}" data-url="{$deleteUrl}" title="Delete">
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
      <polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/>
      <path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/>
    </svg>
  </button>
</div>
HTML;
    }
}
