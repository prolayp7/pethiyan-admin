<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\VideoStorySlide;
use Illuminate\Http\JsonResponse;

class VideoStorySectionApiController extends Controller
{
    public function index(): JsonResponse
    {
        $videos = VideoStorySlide::active()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn ($video) => [
                'id' => $video->id,
                'title' => $video->title,
                'videoUrl' => $video->video_url,
            ]);

        $setting = Setting::where('variable', 'video_story_section')->first();
        $settings = [];

        if ($setting) {
            $settings = is_array($setting->value)
                ? $setting->value
                : (json_decode((string) $setting->value, true) ?: []);
        }

        return response()->json([
            'videos' => $videos,
            'settings' => [
                'isActive' => filter_var($settings['is_active'] ?? true, FILTER_VALIDATE_BOOL),
                'eyebrow' => $settings['eyebrow'] ?? 'SHOP & DISCOVER',
                'heading' => $settings['heading'] ?? 'Real Products, Real Stories',
                'subheading' => $settings['subheading'] ?? 'Watch our packaging in action — trusted by brands across the country.',
                'placement' => $settings['placement'] ?? 'after_recently_viewed',
                'autoplayEnabled' => filter_var($settings['autoplay_enabled'] ?? true, FILTER_VALIDATE_BOOL),
                'autoplayDelay' => (int) ($settings['autoplay_delay'] ?? 4500),
                'transitionDuration' => (int) ($settings['transition_duration'] ?? 420),
                'animationStyle' => $settings['animation_style'] ?? 'slide',
            ],
        ]);
    }
}
