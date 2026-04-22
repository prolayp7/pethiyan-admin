<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;

class PageApiController extends Controller
{
    public function show(string $slug): JsonResponse
    {
        $page = Page::where('slug', $slug)->firstOrFail();

        $base = [
            'slug'             => $page->slug,
            'title'            => $page->title,
            'content'          => $page->content,
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

        $base['content_blocks'] = $page->content_blocks;
        return response()->json($base);
    }
}
