<?php

namespace App\Http\Requests\Menu;

use Illuminate\Foundation\Http\FormRequest;

class StoreMenuRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'        => 'required|string|max:255',
            'slug'        => 'nullable|string|max:255|unique:menus,slug',
            'location'    => 'nullable|in:header,footer',
            'description' => 'nullable|string',
            'is_active'   => 'nullable|boolean',
        ];
    }
}
