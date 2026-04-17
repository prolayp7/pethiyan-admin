<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\Testimonial;
use App\Services\ImageWebpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SocialProofController extends Controller
{
    // ──────────────────────────────────────────────────────────────────────────
    // Index
    // ──────────────────────────────────────────────────────────────────────────

    public function show(): View
    {
        $testimonials = Testimonial::orderBy('sort_order')->orderBy('id')->get();
        $settings     = $this->getSectionSettings();

        return view('admin.social-proof.index', compact('testimonials', 'settings'));
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Testimonials CRUD
    // ──────────────────────────────────────────────────────────────────────────

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'stars'        => 'required|integer|min:1|max:5',
            'quote'        => 'required|string|max:1000',
            'name'         => 'required|string|max:120',
            'title'        => 'nullable|string|max:120',
            'avatar_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'is_active'    => 'nullable|boolean',
        ]);

        $data['sort_order'] = Testimonial::max('sort_order') + 1;
        $data['is_active']  = $request->boolean('is_active', true);

        if ($request->hasFile('avatar_image')) {
            $data['avatar_image'] = $this->storeAvatarImage($request->file('avatar_image'));
        }

        $testimonial = Testimonial::create($data);
        $this->triggerFrontendRevalidate();

        return response()->json([
            'success'     => true,
            'message'     => 'Testimonial created successfully.',
            'testimonial' => $testimonial->append('image_url'),
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $testimonial = Testimonial::findOrFail($id);

        $data = $request->validate([
            'stars'        => 'required|integer|min:1|max:5',
            'quote'        => 'required|string|max:1000',
            'name'         => 'required|string|max:120',
            'title'        => 'nullable|string|max:120',
            'avatar_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'is_active'    => 'nullable|boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active', $testimonial->is_active);

        if ($request->hasFile('avatar_image')) {
            // Delete old image if local
            if ($testimonial->avatar_image && !str_starts_with($testimonial->avatar_image, 'http')) {
                Storage::disk('public')->delete($testimonial->avatar_image);
            }
            $data['avatar_image'] = $this->storeAvatarImage($request->file('avatar_image'));
        }

        $testimonial->update($data);
        $this->triggerFrontendRevalidate();

        return response()->json([
            'success'     => true,
            'message'     => 'Testimonial updated successfully.',
            'testimonial' => $testimonial->fresh()->append('image_url'),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $testimonial = Testimonial::findOrFail($id);

        if ($testimonial->avatar_image && !str_starts_with($testimonial->avatar_image, 'http')) {
            Storage::disk('public')->delete($testimonial->avatar_image);
        }

        $testimonial->delete();
        $this->triggerFrontendRevalidate();

        return response()->json(['success' => true, 'message' => 'Testimonial deleted.']);
    }

    public function toggle(int $id): JsonResponse
    {
        $testimonial = Testimonial::findOrFail($id);
        $testimonial->update(['is_active' => !$testimonial->is_active]);
        $this->triggerFrontendRevalidate();

        return response()->json([
            'success'   => true,
            'is_active' => $testimonial->is_active,
            'message'   => 'Testimonial ' . ($testimonial->is_active ? 'activated' : 'deactivated') . '.',
        ]);
    }

    public function reorder(Request $request): JsonResponse
    {
        $request->validate([
            'order'   => 'required|array',
            'order.*' => 'integer|exists:testimonials,id',
        ]);

        foreach ($request->order as $position => $testimonialId) {
            Testimonial::where('id', $testimonialId)->update(['sort_order' => $position + 1]);
        }
        $this->triggerFrontendRevalidate();

        return response()->json(['success' => true, 'message' => 'Order saved.']);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Section Settings
    // ──────────────────────────────────────────────────────────────────────────

    public function updateSettings(Request $request): JsonResponse
    {
        $request->merge([
            'is_active' => $request->boolean('is_active'),
        ]);

        $data = $request->validate([
            'is_active'  => 'boolean',
            'eyebrow'    => 'nullable|string|max:120',
            'heading'    => 'nullable|string|max:255',
            'subheading' => 'nullable|string|max:255',
            'placement'  => 'required|in:after_hero,after_categories,after_featured_products,after_your_items,after_recently_viewed,after_video_stories,after_why_choose_us,after_promo_banner,after_newsletter',
        ]);

        Setting::updateOrCreate(
            ['variable' => 'social_proof_section'],
            ['value'    => json_encode($data)]
        );
        $this->triggerFrontendRevalidate();

        return response()->json(['success' => true, 'message' => 'Section settings saved.']);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────────────────────────────────

    private function getSectionSettings(): array
    {
        $setting = Setting::where('variable', 'social_proof_section')->first();
        $value   = [];
        if ($setting) {
            $value = is_array($setting->value)
                ? $setting->value
                : (json_decode((string) $setting->value, true) ?: []);
        }

        return [
            'is_active'  => $value['is_active']  ?? true,
            'eyebrow'    => $value['eyebrow']     ?? 'SOCIAL PROOF',
            'heading'    => $value['heading']     ?? 'What Our Customers Say',
            'subheading' => $value['subheading']  ?? 'Trusted by over 10,000 brands worldwide',
            'placement'  => $value['placement']   ?? 'after_promo_banner',
        ];
    }

    private function storeAvatarImage($file): string
    {
        $converted = ImageWebpService::convert($file);
        $stored    = Storage::disk('public')->put('testimonials', new \Illuminate\Http\File($converted['path']), ['visibility' => 'public']);
        $dir       = dirname($stored);
        $target    = $dir . '/' . $converted['filename'];
        if ($stored !== $target) {
            Storage::disk('public')->move($stored, $target);
            $stored = $target;
        }
        if ($converted['isWebp']) {
            @unlink($converted['path']);
        }
        return $stored;
    }

    private function triggerFrontendRevalidate(): void
    {
        $frontendUrl = rtrim((string) env('FRONTEND_APP_URL', ''), '/');
        $secret      = (string) env('FRONTEND_REVALIDATE_SECRET', '');

        if ($frontendUrl === '' || $secret === '') {
            return;
        }

        try {
            Http::timeout(3)->post("{$frontendUrl}/api/revalidate", [
                'secret' => $secret,
                'tags'   => ['social-proof'],
                'paths'  => ['/'],
            ]);
        } catch (\Throwable $e) {
            Log::warning('Social proof revalidation request failed.', [
                'message' => $e->getMessage(),
            ]);
        }
    }
}
