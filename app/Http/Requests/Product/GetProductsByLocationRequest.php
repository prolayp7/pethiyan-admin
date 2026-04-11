<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GetProductsByLocationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'per_page' => 'integer|min:1|max:100',
            'page' => 'integer|min:1',
            'categories' => 'string|nullable',
            'brands' => 'string|nullable',
            'sort' => ['string', 'nullable', Rule::in(['price_asc', 'price_desc', 'relevance', 'avg_rated', 'best_seller', 'featured'])],
            'store' => 'string|nullable|max:255',
            'exclude_product' => 'string|nullable',
            'search' => 'string|nullable|min:2|max:255',
            'include_child_categories' => 'nullable|boolean'
        ];
    }
}
