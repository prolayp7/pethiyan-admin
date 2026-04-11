<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\VideoStorySlide;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class VideoStorySectionController extends Controller
{
    public function show(): View
    {
        $videos = VideoStorySlide::orderBy('sort_order')->orderBy('id')->get();
        $settings = $this->getSectionSettings();

        return view('admin.video-story-section.index', compact('videos', 'settings'));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => 'required|string|max:120',
            'video' => 'required|file|mimetypes:video/mp4,video/webm,video/quicktime|max:51200',
            'is_active' => 'nullable|boolean',
        ]);

        $data['sort_order'] = (VideoStorySlide::max('sort_order') ?? 0) + 1;
        $data['is_active'] = $request->boolean('is_active', true);
        $data['video_path'] = $request->file('video')->store('video-stories', 'public');

        $video = VideoStorySlide::create($data);
        $this->triggerFrontendRevalidate();

        return response()->json([
            'success' => true,
            'message' => 'Video added successfully.',
            'video' => $video->fresh(),
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $video = VideoStorySlide::findOrFail($id);

        $data = $request->validate([
            'title' => 'required|string|max:120',
            'video' => 'nullable|file|mimetypes:video/mp4,video/webm,video/quicktime|max:51200',
            'is_active' => 'nullable|boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active', $video->is_active);

        if ($request->hasFile('video')) {
            if ($video->video_path && !str_starts_with($video->video_path, 'http')) {
                Storage::disk('public')->delete($video->video_path);
            }

            $data['video_path'] = $request->file('video')->store('video-stories', 'public');
        }

        $video->update($data);
        $this->triggerFrontendRevalidate();

        return response()->json([
            'success' => true,
            'message' => 'Video updated successfully.',
            'video' => $video->fresh(),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $video = VideoStorySlide::findOrFail($id);

        if ($video->video_path && !str_starts_with($video->video_path, 'http')) {
            Storage::disk('public')->delete($video->video_path);
        }

        $video->delete();
        $this->triggerFrontendRevalidate();

        return response()->json([
            'success' => true,
            'message' => 'Video deleted successfully.',
        ]);
    }

    public function toggle(int $id): JsonResponse
    {
        $video = VideoStorySlide::findOrFail($id);
        $video->update(['is_active' => !$video->is_active]);
        $this->triggerFrontendRevalidate();

        return response()->json([
            'success' => true,
            'is_active' => $video->is_active,
            'message' => 'Video ' . ($video->is_active ? 'activated' : 'deactivated') . '.',
        ]);
    }

    public function reorder(Request $request): JsonResponse
    {
        $request->validate([
            'order' => 'required|array',
            'order.*' => 'integer|exists:video_story_slides,id',
        ]);

        foreach ($request->order as $position => $videoId) {
            VideoStorySlide::where('id', $videoId)->update(['sort_order' => $position + 1]);
        }

        $this->triggerFrontendRevalidate();

        return response()->json([
            'success' => true,
            'message' => 'Video order saved.',
        ]);
    }

    public function updateSettings(Request $request): JsonResponse
    {
        $data = $request->validate([
            'is_active' => 'required|boolean',
            'eyebrow' => 'nullable|string|max:120',
            'heading' => 'nullable|string|max:255',
            'subheading' => 'nullable|string|max:255',
            'autoplay_enabled' => 'required|boolean',
            'autoplay_delay' => 'required|integer|min:1500|max:20000',
            'transition_duration' => 'required|integer|min:0|max:2000',
            'animation_style' => 'required|in:slide,fade,none',
        ]);

        Setting::updateOrCreate(
            ['variable' => 'video_story_section'],
            ['value' => json_encode($data)]
        );

        $this->triggerFrontendRevalidate();

        return response()->json([
            'success' => true,
            'message' => 'Section settings saved.',
        ]);
    }

    private function getSectionSettings(): array
    {
        $setting = Setting::where('variable', 'video_story_section')->first();
        $value = [];

        if ($setting) {
            $value = is_array($setting->value)
                ? $setting->value
                : (json_decode((string) $setting->value, true) ?: []);
        }

        return [
            'is_active' => $value['is_active'] ?? true,
            'eyebrow' => $value['eyebrow'] ?? 'SHOP & DISCOVER',
            'heading' => $value['heading'] ?? 'Real Products, Real Stories',
            'subheading' => $value['subheading'] ?? 'Watch our packaging in action — trusted by brands across the country.',
            'autoplay_enabled' => filter_var($value['autoplay_enabled'] ?? true, FILTER_VALIDATE_BOOL),
            'autoplay_delay' => (int) ($value['autoplay_delay'] ?? 4500),
            'transition_duration' => (int) ($value['transition_duration'] ?? 420),
            'animation_style' => $value['animation_style'] ?? 'slide',
        ];
    }

    private function triggerFrontendRevalidate(): void
    {
        $frontendUrl = rtrim((string) env('FRONTEND_APP_URL', ''), '/');
        $secret = (string) env('FRONTEND_REVALIDATE_SECRET', '');

        if ($frontendUrl === '' || $secret === '') {
            return;
        }

        try {
            Http::timeout(3)->post("{$frontendUrl}/api/revalidate", [
                'secret' => $secret,
                'tags' => ['video-story-section'],
                'paths' => ['/'],
            ]);
        } catch (\Throwable $e) {
            Log::warning('Video story section revalidation request failed.', [
                'message' => $e->getMessage(),
            ]);
        }
    }
}
