<?php

namespace App\Http\Requests\Menu;

use App\Enums\Menu\MenuItemTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreMenuItemRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'label'        => 'required|string|max:255',
            'href'         => 'nullable|string|max:500',
            'type'         => ['required', new Enum(MenuItemTypeEnum::class)],
            'target'       => 'nullable|in:_self,_blank',
            'parent_id'    => 'nullable|integer|exists:menu_items,id',
            'icon'         => 'nullable|string|max:100',
            'description'  => 'nullable|string|max:500',
            'accent_color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'badge'        => 'nullable|string|max:50',
            'sort_order'   => 'nullable|integer|min:0',
            'is_active'    => 'nullable|boolean',
        ];
    }
}
