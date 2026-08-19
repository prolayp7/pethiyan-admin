<?php

namespace App\Http\Requests\User\Order;

use Illuminate\Foundation\Http\FormRequest;

class InitiateEasepayPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'address_id' => ['required', 'numeric', 'exists:addresses,id'],
            'promo_code' => ['nullable', 'string', 'max:50'],
            'gift_card' => ['nullable', 'string', 'max:50'],
            'rush_delivery' => ['boolean', 'nullable'],
            'use_wallet' => ['boolean', 'nullable'],
            'order_note' => ['nullable', 'string', 'max:500'],
            'redirect_url' => ['nullable', 'url'],
            'delivery_charge' => ['nullable', 'numeric', 'min:0'],
            'shipping_rate_id' => ['nullable', 'integer'],
        ];
    }
}
