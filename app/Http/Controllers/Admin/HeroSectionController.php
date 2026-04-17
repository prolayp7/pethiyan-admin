<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AdminPermissionEnum;
use App\Http\Controllers\Controller;
use App\Models\HeroSlide;
use App\Models\HeroTrustBadge;
use App\Models\Setting;
use App\Services\ImageWebpService;
use App\Traits\ChecksPermissions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class HeroSectionController extends Controller
{
    use ChecksPermissions;

    public function __construct()
    {
        $this->middleware(function (Request $request, $next) {
            if ($response = $this->authorizeHomePagePermission($request)) {
                return $response;
            }

            return $next($request);
        });
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Index
    // ──────────────────────────────────────────────────────────────────────────

    public function show(): View
    {
        $slides   = HeroSlide::orderBy('sort_order')->orderBy('id')->get();
        $badges   = HeroTrustBadge::orderBy('sort_order')->orderBy('id')->get();
        $settings = $this->getCarouselSettings();

        $availableIcons = $this->availableIcons();

        return view('admin.hero-section.index', compact('slides', 'badges', 'settings', 'availableIcons'));
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Slides CRUD
    // ──────────────────────────────────────────────────────────────────────────

    public function storeSlide(Request $request): JsonResponse
    {
        $data = $request->validate([
            'eyebrow'             => 'required|string|max:120',
            'heading'             => 'required|string|max:300',
            'description'         => 'nullable|string|max:500',
            'primary_cta_label'   => 'required|string|max:120',
            'primary_cta_href'    => 'required|string|max:500',
            'secondary_cta_label' => 'required|string|max:120',
            'secondary_cta_href'  => 'required|string|max:500',
            'image'               => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'is_active'           => 'nullable|boolean',
        ]);

        $data['sort_order'] = HeroSlide::max('sort_order') + 1;
        $data['is_active']  = $request->boolean('is_active', true);

        if ($request->hasFile('image')) {
            $converted = ImageWebpService::convert($request->file('image'));
            $stored    = Storage::disk('public')->put('hero-slides', new \Illuminate\Http\File($converted['path']), ['visibility' => 'public']);
            // Rename to use the WebP filename derived from conversion
            $dir      = dirname($stored);
            $target   = $dir . '/' . $converted['filename'];
            if ($stored !== $target) {
                Storage::disk('public')->move($stored, $target);
                $stored = $target;
            }
            $data['image'] = $stored;
            if ($converted['isWebp']) {
                @unlink($converted['path']);
            }
        }

        $slide = HeroSlide::create($data);
        $this->triggerFrontendHeroRevalidate();

        return response()->json([
            'success' => true,
            'message' => 'Slide created successfully.',
            'slide'   => $slide->append('image_url'),
        ]);
    }

    public function updateSlide(Request $request, int $id): JsonResponse
    {
        $slide = HeroSlide::findOrFail($id);

        $data = $request->validate([
            'eyebrow'             => 'required|string|max:120',
            'heading'             => 'required|string|max:300',
            'description'         => 'nullable|string|max:500',
            'primary_cta_label'   => 'required|string|max:120',
            'primary_cta_href'    => 'required|string|max:500',
            'secondary_cta_label' => 'required|string|max:120',
            'secondary_cta_href'  => 'required|string|max:500',
            'image'               => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'is_active'           => 'nullable|boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active', $slide->is_active);

        if ($request->hasFile('image')) {
            // Delete old image if stored locally
            if ($slide->image && !str_starts_with($slide->image, 'http')) {
                Storage::disk('public')->delete($slide->image);
            }
            $converted = ImageWebpService::convert($request->file('image'));
            $stored    = Storage::disk('public')->put('hero-slides', new \Illuminate\Http\File($converted['path']), ['visibility' => 'public']);
            $dir      = dirname($stored);
            $target   = $dir . '/' . $converted['filename'];
            if ($stored !== $target) {
                Storage::disk('public')->move($stored, $target);
                $stored = $target;
            }
            $data['image'] = $stored;
            if ($converted['isWebp']) {
                @unlink($converted['path']);
            }
        }

        $slide->update($data);
        $this->triggerFrontendHeroRevalidate();

        return response()->json([
            'success' => true,
            'message' => 'Slide updated successfully.',
            'slide'   => $slide->fresh()->append('image_url'),
        ]);
    }

    public function destroySlide(int $id): JsonResponse
    {
        $slide = HeroSlide::findOrFail($id);

        if ($slide->image && !str_starts_with($slide->image, 'http')) {
            Storage::disk('public')->delete($slide->image);
        }

        $slide->delete();
        $this->triggerFrontendHeroRevalidate();

        return response()->json(['success' => true, 'message' => 'Slide deleted.']);
    }

    public function toggleSlide(int $id): JsonResponse
    {
        $slide = HeroSlide::findOrFail($id);
        $slide->update(['is_active' => !$slide->is_active]);
        $this->triggerFrontendHeroRevalidate();

        return response()->json([
            'success'   => true,
            'is_active' => $slide->is_active,
            'message'   => 'Slide ' . ($slide->is_active ? 'activated' : 'deactivated') . '.',
        ]);
    }

    public function reorderSlides(Request $request): JsonResponse
    {
        $request->validate(['order' => 'required|array', 'order.*' => 'integer|exists:hero_slides,id']);

        foreach ($request->order as $position => $slideId) {
            HeroSlide::where('id', $slideId)->update(['sort_order' => $position + 1]);
        }
        $this->triggerFrontendHeroRevalidate();

        return response()->json(['success' => true, 'message' => 'Order saved.']);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Trust Badges CRUD
    // ──────────────────────────────────────────────────────────────────────────

    public function storeBadge(Request $request): JsonResponse
    {
        $data = $request->validate([
            'icon_name' => 'required|string|max:80',
            'label'     => 'required|string|max:80',
            'is_active' => 'nullable|boolean',
        ]);

        $data['sort_order'] = HeroTrustBadge::max('sort_order') + 1;
        $data['is_active']  = $request->boolean('is_active', true);

        $badge = HeroTrustBadge::create($data);
        $this->triggerFrontendHeroRevalidate();

        return response()->json(['success' => true, 'message' => 'Badge created.', 'badge' => $badge]);
    }

    public function updateBadge(Request $request, int $id): JsonResponse
    {
        $badge = HeroTrustBadge::findOrFail($id);

        $data = $request->validate([
            'icon_name' => 'required|string|max:80',
            'label'     => 'required|string|max:80',
            'is_active' => 'nullable|boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active', $badge->is_active);
        $badge->update($data);
        $this->triggerFrontendHeroRevalidate();

        return response()->json(['success' => true, 'message' => 'Badge updated.', 'badge' => $badge->fresh()]);
    }

    public function destroyBadge(int $id): JsonResponse
    {
        HeroTrustBadge::findOrFail($id)->delete();
        $this->triggerFrontendHeroRevalidate();
        return response()->json(['success' => true, 'message' => 'Badge deleted.']);
    }

    public function toggleBadge(int $id): JsonResponse
    {
        $badge = HeroTrustBadge::findOrFail($id);
        $badge->update(['is_active' => !$badge->is_active]);
        $this->triggerFrontendHeroRevalidate();

        return response()->json([
            'success'   => true,
            'is_active' => $badge->is_active,
            'message'   => 'Badge ' . ($badge->is_active ? 'activated' : 'deactivated') . '.',
        ]);
    }

    public function reorderBadges(Request $request): JsonResponse
    {
        $request->validate(['order' => 'required|array', 'order.*' => 'integer|exists:hero_trust_badges,id']);

        foreach ($request->order as $position => $badgeId) {
            HeroTrustBadge::where('id', $badgeId)->update(['sort_order' => $position + 1]);
        }
        $this->triggerFrontendHeroRevalidate();

        return response()->json(['success' => true, 'message' => 'Order saved.']);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Carousel Settings
    // ──────────────────────────────────────────────────────────────────────────

    public function updateSettings(Request $request): JsonResponse
    {
        $data = $request->validate([
            'autoplay_enabled' => 'required|boolean',
            'autoplay_delay'   => 'required|integer|min:1000|max:30000',
            'hero_height'      => 'required|integer|min:360|max:980',
        ]);

        Setting::updateOrCreate(
            ['variable' => 'hero_section'],
            ['value'    => json_encode($data)]
        );
        $this->triggerFrontendHeroRevalidate();

        return response()->json(['success' => true, 'message' => 'Carousel settings saved.']);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────────────────────────────────

    private function getCarouselSettings(): array
    {
        $setting = Setting::where('variable', 'hero_section')->first();
        $value   = [];
        if ($setting) {
            $value = is_array($setting->value)
                ? $setting->value
                : (json_decode((string) $setting->value, true) ?: []);
        }

        return [
            'autoplay_enabled' => $value['autoplay_enabled'] ?? true,
            'autoplay_delay'   => $value['autoplay_delay']   ?? 5000,
            'hero_height'      => $value['hero_height']      ?? 620,
        ];
    }

    private function triggerFrontendHeroRevalidate(): void
    {
        $frontendUrl = rtrim((string) env('FRONTEND_APP_URL', ''), '/');
        $secret = (string) env('FRONTEND_REVALIDATE_SECRET', '');

        if ($frontendUrl === '' || $secret === '') {
            return;
        }

        try {
            Http::timeout(3)->post("{$frontendUrl}/api/revalidate", [
                'secret' => $secret,
                'tags' => ['hero-section'],
                'paths' => ['/'],
            ]);
        } catch (\Throwable $e) {
            Log::warning('Hero revalidation request failed.', [
                'message' => $e->getMessage(),
            ]);
        }
    }

    public static function availableIcons(): array
    {
        return [
            'shield-check'   => 'Shield Check',
            'leaf'           => 'Leaf',
            'package-check'  => 'Package Check',
            'truck'          => 'Truck',
            'star'           => 'Star',
            'award'          => 'Award',
            'check-circle'   => 'Check Circle',
            'zap'            => 'Zap',
            'globe'          => 'Globe',
            'heart'          => 'Heart',
            'recycle'        => 'Recycle',
            'box'            => 'Box',
            'clock'          => 'Clock',
            'thumbs-up'      => 'Thumbs Up',
            'lock'           => 'Lock',
            'smile'          => 'Smile',
        ];
    }

    private function authorizeHomePagePermission(Request $request)
    {
        $permission = match ($request->route()?->getActionMethod()) {
            'show' => AdminPermissionEnum::HOME_PAGE_VIEW->value,
            'storeSlide', 'updateSlide', 'destroySlide', 'toggleSlide', 'reorderSlides', 'storeBadge', 'updateBadge', 'destroyBadge', 'toggleBadge', 'reorderBadges', 'updateSettings' => AdminPermissionEnum::HOME_PAGE_EDIT->value,
            default => null,
        };

        if ($permission === null || $this->hasPermission($permission)) {
            return null;
        }

        if ($request->expectsJson() || $request->ajax()) {
            return $this->unauthorizedResponse();
        }

        abort(403, 'Unauthorized action.');
    }
}
