<?php

namespace App\Http\Requests\MegaMenu;

use Illuminate\Foundation\Http\FormRequest;

class StoreColumnRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'heading'    => 'required|string|max:255',
            'sort_order' => 'nullable|integer|min:0',
        ];
    }
}
