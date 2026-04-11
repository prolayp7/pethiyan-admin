<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Triggers Next.js on-demand ISR revalidation whenever products or categories
 * change in the admin. This purges the frontend's server-side cache so the
 * next visitor gets fresh data immediately instead of waiting for the 5-minute
 * fallback revalidation interval.
 *
 * Requires in .env:
 *   FRONTEND_URL=https://pethiyan.com
 *   REVALIDATE_SECRET=<same secret set in frontend .env.local>
 */
class FrontendRevalidateService
{
    /**
     * Fire-and-forget revalidation. Failures are logged but never throw.
     *
     * @param  string[]  $tags   Cache tags to purge, e.g. ['products', 'featured-products']
     * @param  string[]  $paths  Extra paths to revalidate, e.g. ['/shop']
     */
    public static function revalidate(array $tags = ['products'], array $paths = []): void
    {
        $frontendUrl = rtrim((string) config('app.frontend_url', ''), '/');
        $secret      = (string) config('app.revalidate_secret', '');

        if (empty($frontendUrl) || empty($secret)) {
            Log::warning('FrontendRevalidateService: FRONTEND_URL or REVALIDATE_SECRET is not set. Skipping revalidation.');
            return;
        }

        try {
            Http::timeout(5)
                ->withHeaders(['x-revalidate-secret' => $secret])
                ->post("{$frontendUrl}/api/revalidate", [
                    'tags'  => $tags,
                    'paths' => $paths,
                ]);
        } catch (\Throwable $e) {
            // Never let a failed revalidation break the admin save action.
            Log::warning('FrontendRevalidateService: revalidation request failed.', [
                'error' => $e->getMessage(),
                'tags'  => $tags,
            ]);
        }
    }

    /** Shorthand for product-related changes. */
    public static function revalidateProducts(): void
    {
        static::revalidate(
            tags:  ['products', 'featured-products'],
            paths: ['/shop', '/'],
        );
    }
}
