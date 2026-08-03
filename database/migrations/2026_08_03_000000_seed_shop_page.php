<?php

use App\Models\Page;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Page::firstOrCreate(
            ['slug' => 'shop'],
            [
                'title' => 'Shop All Products',
                'content' => null,
                'content_blocks' => [],
                'system_page' => true,
                'status' => 'active',
            ]
        );
    }

    public function down(): void
    {
        Page::where('slug', 'shop')->delete();
    }
};
