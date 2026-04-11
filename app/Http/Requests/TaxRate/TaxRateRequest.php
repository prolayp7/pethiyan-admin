<?php

namespace App\Http\Requests\TaxRate;

use Illuminate\Foundation\Http\FormRequest;

class TaxRateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'       => 'required|string|max:255|unique:tax_rates,title,' . $this->route('id'),
            'rate'        => 'required|numeric|min:0|max:100',
            'gst_slab'    => 'nullable|in:0,5,12,18,28',
            'cgst_rate'   => 'nullable|numeric|min:0|max:50',
            'sgst_rate'   => 'nullable|numeric|min:0|max:50',
            'igst_rate'   => 'nullable|numeric|min:0|max:100',
            'description' => 'nullable|string|max:500',
            'is_gst'      => 'nullable|boolean',
            'is_active'   => 'nullable|boolean',
        ];
    }
}
