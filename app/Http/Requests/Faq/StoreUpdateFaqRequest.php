<?php

namespace App\Http\Requests\Faq;

use App\Enums\ActiveInactiveStatusEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreUpdateFaqRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'faq_category_id' => 'nullable|exists:faq_categories,id',
            'question'        => 'required|string|max:1000',
            'answer'          => 'required|string|max:5000',
            'sort_order'      => 'nullable|integer|min:0',
            'status'          => ['nullable', new Enum(ActiveInactiveStatusEnum::class)],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'status'     => $this->status     ?? 'active',
            'sort_order' => $this->sort_order ?? 0,
        ]);
    }

    public function messages(): array
    {
        return [
            'question.required'        => 'The question field is required.',
            'question.max'             => 'The question may not be greater than 1000 characters.',
            'answer.required'          => 'The answer field is required.',
            'answer.max'               => 'The answer may not be greater than 5000 characters.',
            'status.in'                => 'The status must be either active or inactive.',
            'faq_category_id.exists'   => 'The selected FAQ category is invalid.',
        ];
    }
}
