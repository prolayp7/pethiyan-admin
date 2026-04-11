<?php

namespace App\Observers;

use App\Models\Product;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProductObserver
{
    /**
     * Notify the Next.js frontend to purge its ISR cache for products.
     * Called after any product create / update / delete.
     */
    private function revalidateFrontend(): void
    {
        $frontendUrl = rtrim((string) config('app.frontend_url', ''), '/');
        $secret      = (string) config('app.revalidate_secret', '');

        if ($frontendUrl === '' || $secret === '') {
            return; // Secret not configured — skip silently
        }

        try {
            Http::timeout(5)->post("{$frontendUrl}/api/revalidate", [
                'secret' => $secret,
                'tags'   => ['featured-products', 'products'],
                'paths'  => ['/'],
            ]);
        } catch (\Throwable $e) {
            // Never fail a product save because the frontend is unreachable
            Log::warning('Frontend revalidation failed: ' . $e->getMessage());
        }
    }

    public function created(Product $product): void
    {
        $this->revalidateFrontend();
    }

    public function updated(Product $product): void
    {
        $this->revalidateFrontend();
    }

    public function deleted(Product $product): void
    {
        $this->revalidateFrontend();
    }
}
