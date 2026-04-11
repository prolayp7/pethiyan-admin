<?php

namespace App\Http\Requests\MegaMenu;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLinkRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'label'      => 'required|string|max:255',
            'href'       => 'required|string|max:500',
            'target'     => 'nullable|in:_self,_blank',
            'is_active'  => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ];
    }
}
