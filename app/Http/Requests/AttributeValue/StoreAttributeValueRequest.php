<?php

namespace App\Http\Requests\AttributeValue;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreAttributeValueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'attribute_id' => 'required|exists:global_product_attributes,id',
            'values' => 'required|array',
            'values.*' => 'required|string|max:255',
            'swatche_value' => 'required|array',
            'swatche_value.*' => 'required'
        ];
    }
}
