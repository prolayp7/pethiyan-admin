<?php

namespace App\Http\Requests\MegaMenu;

use Illuminate\Foundation\Http\FormRequest;

class StorePanelRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'label'        => 'required|string|max:255',
            'href'         => 'required|string|max:500',
            'accent_color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'image_path'   => 'nullable|string|max:500',
            'panel_image'  => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'tagline'      => 'nullable|string|max:255',
            'sort_order'   => 'nullable|integer|min:0',
            'is_active'    => 'nullable|boolean',
        ];
    }
}
