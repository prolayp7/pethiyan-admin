<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FaqCategory extends Model
{
    protected $fillable = ['name', 'icon', 'sort_order', 'status'];

    public function faqs(): HasMany
    {
        return $this->hasMany(Faq::class, 'faq_category_id');
    }

    public function activeFaqs(): HasMany
    {
        return $this->hasMany(Faq::class, 'faq_category_id')
                    ->where('status', 'active')
                    ->orderBy('sort_order');
    }
}
