<?php

namespace App\Http\Requests\Menu;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMenuRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $id = $this->route('menu');
        return [
            'name'        => 'required|string|max:255',
            'slug'        => "nullable|string|max:255|unique:menus,slug,{$id}",
            'location'    => 'nullable|in:header,footer',
            'description' => 'nullable|string',
            'is_active'   => 'nullable|boolean',
        ];
    }
}
