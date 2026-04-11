<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Page;

class CmsPagesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pages = [
            [
                'slug' => 'about-us',
                'title' => 'About Us',
                'content' => '<h2>About our online store</h2><p>Founded in 2017, Secureship began with a simple mission...</p>',
                'system_page' => true,
            ],
            [
                'slug' => 'terms-and-conditions',
                'title' => 'Terms & Conditions',
                'content' => '<h2>Terms and Conditions</h2><p>Our terms of service goes here...</p>',
                'system_page' => true,
            ],
            [
                'slug' => 'privacy-policy',
                'title' => 'Privacy Policy',
                'content' => '<h2>Privacy Policy</h2><p>Our privacy policy goes here...</p>',
                'system_page' => true,
            ],
            [
                'slug' => 'enquiry-form',
                'title' => 'Enquiry Form Settings',
                'content' => '<h2>Contact us for custom order</h2><p>We collaborate with people and brands, lets build something great together.</p>',
                'system_page' => true,
            ],
            [
                'slug' => 'contact-us',
                'title' => 'Contact Us Settings',
                'content' => '<h2>Get in Touch</h2><p>Have questions? Reach out to us.</p>',
                'system_page' => true,
            ],
        ];

        foreach ($pages as $page) {
            Page::firstOrCreate(
                ['slug' => $page['slug']],
                $page
            );
        }
    }
}
