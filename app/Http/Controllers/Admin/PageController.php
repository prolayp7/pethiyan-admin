<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AdminPermissionEnum;
use App\Enums\SettingTypeEnum;
use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\Setting;
use App\Services\FrontendRevalidateService;
use App\Traits\ChecksPermissions;
use Illuminate\Http\Request;

class PageController extends Controller
{
    use ChecksPermissions;

    private const CONTACT_PAGE_SLUG = 'contact-us';
    private const ABOUT_PAGE_SLUG = 'about-us';

    public function __construct()
    {
        $this->middleware(function (Request $request, $next) {
            if ($response = $this->authorizePagePermission($request)) {
                return $response;
            }

            return $next($request);
        });
    }

    public function index()
    {
        $pages = Page::orderBy('id')->get();
        return view('admin.pages.index', compact('pages'));
    }

    public function edit(Page $page)
    {
        $systemSettings = [];
        if ($page->slug === self::CONTACT_PAGE_SLUG) {
            $setting = Setting::find(SettingTypeEnum::SYSTEM());
            $systemSettings = is_array($setting?->value) ? $setting->value : [];
        }
        return view('admin.pages.edit', compact('page', 'systemSettings'));
    }

    public function update(Request $request, Page $page)
    {
        if ($page->slug === self::CONTACT_PAGE_SLUG) {
            return $this->updateContactPage($request, $page);
        }

        if ($page->slug === self::ABOUT_PAGE_SLUG) {
            return $this->updateAboutPage($request, $page);
        }

        $validated = $request->validate([
            'title'            => 'required|string|max:255',
            'content'          => 'nullable|string',
            'content_blocks'   => 'nullable|string',
            'meta_title'       => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
        ]);

        $data = $validated;
        if (!empty($data['content_blocks'])) {
            $decoded = json_decode($data['content_blocks'], true);
            $data['content_blocks'] = $decoded ?: null;
        }

        $page->update($data);

        return redirect()->route('admin.pages.index')->with('success', 'Page updated successfully.');
    }

    public function uploadMedia(Request $request, Page $page)
    {
        $this->authorizePagePermission($request);

        $request->validate([
            'file' => 'required|file|mimes:jpg,jpeg,png,gif,webp|max:5120',
        ]);

        if ($request->hasFile('file')) {
            $media = $page->addMediaFromRequest('file')->toMediaCollection('page_images');

            return response()->json([
                'success'  => true,
                'media_id' => $media->id,
                'url'      => $media->getUrl(),
            ]);
        }

        return response()->json(['success' => false, 'message' => 'No file uploaded'], 422);
    }

    private function updateContactPage(Request $request, Page $page)
    {
        $validated = $request->validate([
            'title'              => 'required|string|max:255',
            'introTitle'         => 'nullable|string|max:255',
            'introText'          => 'nullable|string|max:1000',
            'phoneNumbers'       => 'nullable|string|max:500',
            'phoneNote'          => 'nullable|string|max:255',
            'whatsappNumber'     => 'nullable|string|max:20',
            'emails'             => 'nullable|string|max:500',
            'emailNote'          => 'nullable|string|max:255',
            'officeName'         => 'nullable|string|max:255',
            'officeAddress'      => 'nullable|string|max:500',
            'officeNote'         => 'nullable|string|max:255',
            'businessHoursLine1' => 'nullable|string|max:255',
            'businessHoursLine2' => 'nullable|string|max:255',
            'businessHoursNote'  => 'nullable|string|max:255',
            'meta_title'         => 'nullable|string|max:255',
            'meta_description'   => 'nullable|string',
        ]);

        $contactBlocks = [
            'introTitle'         => $validated['introTitle'] ?? '',
            'introText'          => $validated['introText'] ?? '',
            'phoneNumbers'       => $validated['phoneNumbers'] ?? '',
            'phoneNote'          => $validated['phoneNote'] ?? '',
            'whatsappNumber'     => preg_replace('/\D/', '', $validated['whatsappNumber'] ?? ''),
            'emails'             => $validated['emails'] ?? '',
            'emailNote'          => $validated['emailNote'] ?? '',
            'officeName'         => $validated['officeName'] ?? '',
            'officeAddress'      => $validated['officeAddress'] ?? '',
            'officeNote'         => $validated['officeNote'] ?? '',
            'businessHoursLine1' => $validated['businessHoursLine1'] ?? '',
            'businessHoursLine2' => $validated['businessHoursLine2'] ?? '',
            'businessHoursNote'  => $validated['businessHoursNote'] ?? '',
        ];

        $page->update([
            'title'            => $validated['title'],
            'content_blocks'   => $contactBlocks,
            'meta_title'       => $validated['meta_title'] ?? null,
            'meta_description' => $validated['meta_description'] ?? null,
        ]);

        // Sync primary values back to system settings
        $this->syncContactToSystemSettings($contactBlocks);

        FrontendRevalidateService::revalidate(tags: ['contact-page'], paths: ['/contact']);

        return redirect()->route('admin.pages.index')->with('success', 'Contact page updated successfully.');
    }

    private function updateAboutPage(Request $request, Page $page)
    {
        $validated = $request->validate([
            'title'            => 'required|string|max:255',
            'about_sections'   => 'nullable|string',
            'meta_title'       => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
        ]);

        $decodedSections = json_decode($validated['about_sections'] ?? '[]', true);
        $sections = collect(is_array($decodedSections) ? $decodedSections : [])
            ->map(function ($section) {
                if (!is_array($section)) {
                    return null;
                }

                $heading = trim((string) ($section['heading'] ?? ''));
                $subheading = trim((string) ($section['subheading'] ?? ''));
                $bodyHtml = trim((string) ($section['body_html'] ?? ''));
                $imageUrl = trim((string) ($section['image_url'] ?? ''));
                $imageAlt = trim((string) ($section['image_alt'] ?? ''));
                $imagePosition = strtolower(trim((string) ($section['image_position'] ?? 'right')));

                if ($heading === '' && $subheading === '' && $bodyHtml === '' && $imageUrl === '') {
                    return null;
                }

                return [
                    'subheading'     => mb_substr($subheading, 0, 255),
                    'heading'        => mb_substr($heading, 0, 255),
                    'body_html'      => $bodyHtml,
                    'image_url'      => $imageUrl,
                    'image_alt'      => mb_substr($imageAlt, 0, 255),
                    'image_position' => in_array($imagePosition, ['left', 'right'], true) ? $imagePosition : 'right',
                ];
            })
            ->filter()
            ->values()
            ->all();

        $blocks = is_array($page->content_blocks) ? $page->content_blocks : [];
        $blocks['story_sections'] = $sections;

        $page->update([
            'title'            => $validated['title'],
            'content_blocks'   => $blocks,
            'meta_title'       => $validated['meta_title'] ?? null,
            'meta_description' => $validated['meta_description'] ?? null,
        ]);

        FrontendRevalidateService::revalidate(tags: ['about-page'], paths: ['/about']);

        return redirect()->route('admin.pages.index')->with('success', 'About page updated successfully.');
    }

    /**
     * Push the primary phone, primary email, and office address back to system settings
     * so the two sources stay in sync.
     */
    public static function syncContactToSystemSettings(array $contactBlocks): void
    {
        $setting = Setting::find(SettingTypeEnum::SYSTEM());
        if (!$setting) {
            return;
        }

        $values = is_array($setting->value) ? $setting->value : [];

        $primaryPhone = trim(explode("\n", $contactBlocks['phoneNumbers'] ?? '')[0]);
        $primaryEmail = trim(explode("\n", $contactBlocks['emails'] ?? '')[0]);
        $officeAddress = trim($contactBlocks['officeAddress'] ?? '');

        if ($primaryPhone !== '') {
            $values['sellerSupportNumber'] = $primaryPhone;
        }
        if ($primaryEmail !== '') {
            $values['sellerSupportEmail'] = $primaryEmail;
        }
        if ($officeAddress !== '') {
            $values['companyAddress'] = $officeAddress;
        }

        $setting->update(['value' => $values]);
    }

    /**
     * Push system settings values (support number / email / address) into page 5's
     * content_blocks so the two sources stay in sync.
     */
    public static function syncSystemSettingsToContact(array $systemValues): void
    {
        $page = Page::where('slug', self::CONTACT_PAGE_SLUG)->first();
        if (!$page) {
            return;
        }

        $blocks = is_array($page->content_blocks) ? $page->content_blocks : [];

        if (!empty($systemValues['sellerSupportNumber'])) {
            $existing = $blocks['phoneNumbers'] ?? '';
            $lines = array_filter(array_map('trim', explode("\n", $existing)));
            $lines[0] = $systemValues['sellerSupportNumber'];
            $blocks['phoneNumbers'] = implode("\n", array_values($lines));
        }

        if (!empty($systemValues['sellerSupportEmail'])) {
            $existing = $blocks['emails'] ?? '';
            $lines = array_filter(array_map('trim', explode("\n", $existing)));
            $lines[0] = $systemValues['sellerSupportEmail'];
            $blocks['emails'] = implode("\n", array_values($lines));
        }

        if (!empty($systemValues['companyAddress'])) {
            $blocks['officeAddress'] = $systemValues['companyAddress'];
        }

        $page->update(['content_blocks' => $blocks]);
    }

    private function authorizePagePermission(Request $request)
    {
        $permission = match ($request->route()?->getActionMethod()) {
            'index'                => AdminPermissionEnum::PAGE_VIEW->value,
            'edit', 'update'       => AdminPermissionEnum::PAGE_EDIT->value,
            default                => null,
        };

        if ($permission === null || $this->hasPermission($permission)) {
            return null;
        }

        abort(403, 'Unauthorized action.');
    }
}
