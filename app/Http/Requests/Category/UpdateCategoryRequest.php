<?php

namespace App\Http\Requests\Category;

use App\Enums\Category\CategoryBackgroundTypeEnum;
use App\Enums\CategoryStatusEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class UpdateCategoryRequest extends FormRequest
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
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        $rules = [
            'parent_id' => 'nullable|integer|exists:categories,id',
            'title' => 'required|string|max:255|unique:categories,title,' . $this->route('id'),
            'description' => 'nullable|string',
            'status' => ['nullable', new Enum(CategoryStatusEnum::class)],
            'requires_approval' => 'boolean',
            'commission' => 'nullable|numeric|min:0|max:100',
            'metadata' => 'nullable|array',
            'metadata.seo_title' => 'nullable|string|max:255',
            'metadata.seo_description' => 'nullable|string|max:500',
            'metadata.seo_keywords' => 'nullable|string|max:255',
            'metadata.og_title' => 'nullable|string|max:255',
            'metadata.og_description' => 'nullable|string|max:500',
            'metadata.twitter_title' => 'nullable|string|max:255',
            'metadata.twitter_description' => 'nullable|string|max:500',
            'metadata.twitter_card' => 'nullable|in:summary,summary_large_image,app,player',
            'metadata.schema_mode' => 'nullable|in:auto,custom',
            'metadata.schema_json_ld' => 'nullable|json',
            'is_indexable' => 'nullable|boolean',
        ];

        // Conditionally validate file fields only if a real file is uploaded
        $hasImage = $this->hasFile('image') && $this->file('image')->getSize() > 0;
        $rules['image'] = $hasImage ? 'image|mimes:jpeg,png,jpg,webp|max:5120' : 'nullable';

        $hasBanner = $this->hasFile('banner') && $this->file('banner')->getSize() > 0;
        $rules['banner'] = $hasBanner ? 'image|mimes:jpeg,png,jpg,webp|max:10240' : 'nullable';

        $hasIcon = $this->hasFile('icon') && $this->file('icon')->getSize() > 0;
        $rules['icon'] = $hasIcon ? 'mimes:jpeg,png,jpg,webp,svg' : 'nullable';

        $hasActiveIcon = $this->hasFile('active_icon') && $this->file('active_icon')->getSize() > 0;
        $rules['active_icon'] = $hasActiveIcon ? 'mimes:jpeg,png,jpg,webp,svg' : 'nullable';

        $hasBackgroundImage = $this->hasFile('background_image') && $this->file('background_image')->getSize() > 0;
        $rules['background_image'] = $hasBackgroundImage ? 'image|mimes:jpeg,png,jpg,webp|max:5120' : 'nullable';

        $hasOgImage = $this->hasFile('og_image') && $this->file('og_image')->getSize() > 0;
        $rules['og_image'] = $hasOgImage ? 'image|mimes:jpeg,png,jpg,webp|max:4096' : 'nullable';

        $hasTwitterImage = $this->hasFile('twitter_image') && $this->file('twitter_image')->getSize() > 0;
        $rules['twitter_image'] = $hasTwitterImage ? 'image|mimes:jpeg,png,jpg,webp|max:4096' : 'nullable';

        return $rules;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'status'            => $this->status ?? CategoryStatusEnum::INACTIVE->value,
            'requires_approval' => false, // always auto-approved
            'metadata'          => array_merge($this->metadata ?? [], [
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
