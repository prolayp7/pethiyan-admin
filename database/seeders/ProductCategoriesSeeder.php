<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ProductCategoriesSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Pouches',
            'Mailer Boxes',
            'Tape',
            'Courier Bags',
            'Jars',
            'Sealing Machine',
            'Jar Sealing Wads',
        ];

        foreach ($categories as $title) {
            $slug = Str::slug($title);

            // Skip if slug already exists
            if (DB::table('categories')->where('slug', $slug)->exists()) {
                continue;
            }

            DB::table('categories')->insert([
                'uuid'              => (string) Str::uuid(),
                'parent_id'         => null,
                'title'             => $title,
                'slug'              => $slug,
                'description'       => null,
                'status'            => 'active',
                'requires_approval' => false,
                'commission'        => 0.00,
                'background_type'   => 'color',
                'background_color'  => null,
                'font_color'        => null,
                'metadata'          => json_encode([
                    'seo_title'       => $title,
                    'seo_keywords'    => strtolower($title),
                    'seo_description' => $title . ' - packaging solutions.',
                ]),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }
    }
}
