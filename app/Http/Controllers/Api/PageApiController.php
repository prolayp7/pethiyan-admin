<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;

class PageApiController extends Controller
{
    public function index(): JsonResponse
    {
        $pages = Page::query()
            ->select(['slug', 'title', 'updated_at'])
            ->where('status', 'active')
            ->where('system_page', false)
            ->orderBy('title')
            ->get();

        return response()->json($pages);
    }

    public function show(string $slug): JsonResponse
    {
        $page = Page::where('slug', $slug)
            ->where('status', 'active')
            ->firstOrFail();

        $base = [
            'slug'             => $page->slug,
            'title'            => $page->title,
            'content'          => $page->content,
            'status'           => $page->status,
            'system_page'      => $page->system_page,
            'meta_title'       => $page->meta_title,
            'meta_description' => $page->meta_description,
        ];

        if ($slug === 'contact-us') {
            $blocks = is_array($page->content_blocks) ? $page->content_blocks : [];

            $phoneLines = array_values(array_filter(
                array_map('trim', explode("\n", $blocks['phoneNumbers'] ?? ''))
            ));
            $emailLines = array_values(array_filter(
                array_map('trim', explode("\n", $blocks['emails'] ?? ''))
            ));

            $webSetting = Setting::where('variable', 'web')->first();
            $webValue   = $webSetting ? ($webSetting->value ?? []) : [];

            $base['contact'] = [
                'introTitle'         => $blocks['introTitle'] ?? '',
                'introText'          => $blocks['introText'] ?? '',
                'phoneNumbers'       => $phoneLines,
                'phoneNote'          => $blocks['phoneNote'] ?? '',
                'whatsappNumber'     => $blocks['whatsappNumber'] ?? '',
                'emails'             => $emailLines,
                'emailNote'          => $blocks['emailNote'] ?? '',
                'officeName'         => $blocks['officeName'] ?? '',
                'officeAddress'      => $blocks['officeAddress'] ?? '',
                'officeNote'         => $blocks['officeNote'] ?? '',
                'businessHoursLine1' => $blocks['businessHoursLine1'] ?? '',
                'businessHoursLine2' => $blocks['businessHoursLine2'] ?? '',
                'businessHoursNote'  => $blocks['businessHoursNote'] ?? '',
                'mapLatitude'        => (string) ($webValue['defaultLatitude'] ?? ''),
                'mapLongitude'       => (string) ($webValue['defaultLongitude'] ?? ''),
                'mapIframe'          => (string) ($webValue['mapIframe'] ?? ''),
            ];

            return response()->json($base, 200, ['Cache-Control' => 'no-store']);
        }

        if ($slug === 'shop') {
            $blocks = is_array($page->content_blocks) ? $page->content_blocks : [];

            $base['header_title'] = $blocks['header_title'] ?? null;
            $base['page_content'] = $blocks['page_content'] ?? null;
            $base['seo_title'] = $blocks['seo_title'] ?? null;
            $base['seo_description'] = $blocks['seo_description'] ?? null;
            $base['seo_keywords'] = $blocks['seo_keywords'] ?? null;
            $base['og_title'] = $blocks['og_title'] ?? null;
            $base['og_description'] = $blocks['og_description'] ?? null;
            $base['og_image'] = $this->resolveStoredImageUrl($blocks['og_image'] ?? null);
            $base['og_image_alt'] = $blocks['og_image_alt'] ?? null;
            $base['twitter_title'] = $blocks['twitter_title'] ?? null;
            $base['twitter_description'] = $blocks['twitter_description'] ?? null;
            $base['twitter_card'] = $blocks['twitter_card'] ?? null;
            $base['twitter_image'] = $this->resolveStoredImageUrl($blocks['twitter_image'] ?? null);
            $base['schema_mode'] = $blocks['schema_mode'] ?? 'auto';
            $base['schema_json_ld'] = $blocks['schema_json_ld'] ?? null;
            $base['faqs'] = $blocks['faqs'] ?? [];
            $base['faq_schema_json_ld'] = $blocks['faq_schema_json_ld'] ?? null;

            return response()->json($base);
        }

        $base['content_blocks'] = $page->content_blocks;
        return response()->json($base);
    }

    private function resolveStoredImageUrl(?string $path): ?string
    {
        return !empty($path) ? url('storage/' . ltrim($path, '/')) : null;
    }
}
