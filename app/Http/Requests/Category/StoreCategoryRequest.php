<?php

namespace App\Http\Requests\Category;

use App\Enums\Category\CategoryBackgroundTypeEnum;
use App\Enums\CategoryStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StoreCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization is handled in the controller
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        $rules = [
            'parent_id' => 'nullable|integer|exists:categories,id',
            'title' => 'required|string|max:255|unique:categories,title',
            'description' => 'nullable|string',
            'status' => ['nullable', new Enum(CategoryStatusEnum::class)],
            'requires_approval' => 'boolean',
            'commission' => 'nullable|numeric|min:0|max:100',
            'metadata' => 'nullable|array',
            'metadata.seo_title' => 'nullable|string|max:255',
            'metadata.seo_description' => 'nullable|string|max:500',
            'metadata.seo_keywords' => 'nullable|string|max:1000',
            'metadata.og_title' => 'nullable|string|max:255',
            'metadata.og_description' => 'nullable|string|max:500',
            'metadata.twitter_title' => 'nullable|string|max:250',
            'metadata.twitter_description' => 'nullable|string|max:500',
            'metadata.twitter_card' => 'nullable|in:summary,summary_large_image,app,player',
            'metadata.schema_mode' => 'nullable|in:auto,custom',
            'metadata.schema_json_ld' => 'nullable|json',
            'is_indexable' => 'nullable|boolean',
        ];

        // Conditionally validate file fields only if files are uploaded
        if ($this->hasFile('image')) {
            $rules['image'] = 'image|mimes:jpeg,png,jpg,webp|max:5120';
        } else {
            $rules['image'] = 'nullable';
        }

        if ($this->hasFile('banner')) {
            $rules['banner'] = 'image|mimes:jpeg,png,jpg,webp|max:10240';
        } else {
            $rules['banner'] = 'nullable';
        }

        if ($this->hasFile('icon')) {
            $rules['icon'] = 'image|mimes:jpeg,png,jpg,webp,svg';
        } else {
            $rules['icon'] = 'nullable';
        }

        if ($this->hasFile('active_icon')) {
            $rules['active_icon'] = 'image|mimes:jpeg,png,jpg,webp,svg';
        } else {
            $rules['active_icon'] = 'nullable';
        }

        if ($this->hasFile('background_image')) {
            $rules['background_image'] = 'image|mimes:jpeg,png,jpg,webp|max:5120';
        } else {
            $rules['background_image'] = 'nullable';
        }

        if ($this->hasFile('og_image')) {
            $rules['og_image'] = 'image|mimes:jpeg,png,jpg,webp|max:4096';
        } else {
            $rules['og_image'] = 'nullable';
        }

        if ($this->hasFile('twitter_image')) {
            $rules['twitter_image'] = 'image|mimes:jpeg,png,jpg,webp|max:4096';
        } else {
            $rules['twitter_image'] = 'nullable';
        }

        return $rules;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'status'             => $this->status ?? CategoryStatusEnum::INACTIVE->value,
            'requires_approval'  => false, // always auto-approved
            'metadata'           => array_merge($this->metadata ?? [], [
                'seo_title'       => $this->input('seo_title') ?: null,
                'seo_description' => $this->input('seo_description') ?: null,
                'seo_keywords'    => $this->normalizeSeoKeywords(),
                'og_title' => $this->input('og_title') ?: null,
                'og_description' => $this->input('og_description') ?: null,
                'twitter_title' => $this->input('twitter_title') ?: null,
                'twitter_description' => $this->input('twitter_description') ?: null,
                'twitter_card' => $this->input('twitter_card') ?: null,
                'schema_mode' => $this->input('schema_mode') ?: 'auto',
                'schema_json_ld' => $this->normalizeSchemaJsonLd(),
            ]),
            'is_indexable' => $this->has('is_indexable') ? (bool)$this->input('is_indexable') : true,
        ]);
    }

    private function normalizeSeoKeywords(): ?string
    {
        $keywords = collect(explode(',', (string) $this->input('seo_keywords', '')))
            ->map(function (string $keyword): string {
                return trim((string) preg_replace('/\s+/', ' ', $keyword));
            })
            ->filter()
            ->unique(fn (string $keyword): string => mb_strtolower($keyword))
            ->values();

        return $keywords->isEmpty() ? null : $keywords->implode(', ');
    }

    private function normalizeSchemaJsonLd(): ?string
    {
        $schema = trim((string) $this->input('schema_json_ld', ''));

        return $schema === '' ? null : $schema;
    }
}
