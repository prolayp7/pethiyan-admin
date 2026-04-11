<?php

namespace App\Listeners\Product;

use App\Services\FrontendRevalidateService;

/**
 * Listens to ProductAfterCreate, ProductAfterUpdate, and ProductStatusAfterUpdate
 * events and triggers Next.js ISR cache revalidation so the frontend shop page
 * reflects the change without waiting for the 5-minute fallback interval.
 */
class RevalidateFrontendCache
{
    public function handle(mixed $event): void
    {
        FrontendRevalidateService::revalidateProducts();
    }
}
