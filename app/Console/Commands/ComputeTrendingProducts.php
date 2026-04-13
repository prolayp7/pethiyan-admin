<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\SearchLog;
use App\Models\TrendingProduct;
use App\Enums\Product\ProductStatusEnum;
use App\Enums\Product\ProductVarificationStatusEnum;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ComputeTrendingProducts extends Command
{
    protected $signature   = 'search:compute-trending {--period=weekly : daily or weekly}';
    protected $description = 'Aggregate search logs into trending_products scores';

    public function handle(): int
    {
        $period  = $this->option('period') === 'daily' ? 'daily' : 'weekly';
        $window  = $period === 'daily' ? now()->subDay() : now()->subDays(7);

        $this->info("Computing {$period} trending products…");

        // Count how many times each product appeared in search results
        // We approximate by matching product titles against recent search queries
        $searchTerms = SearchLog::query()
            ->where('created_at', '>=', $window)
            ->select('query', DB::raw('COUNT(*) as freq'))
            ->groupBy('query')
            ->get();

        // Score each active product by how many search queries match its title/tags
        $products = Product::query()
            ->where('verification_status', ProductVarificationStatusEnum::APPROVED->value)
            ->where('status', ProductStatusEnum::ACTIVE->value)
            ->get(['id', 'title', 'tags', 'is_top_product']);

        $scores = [];
        foreach ($products as $product) {
            $score = 0;

            // Base score: is_top_product gives a head-start
            if ($product->is_top_product) {
                $score += 50;
            }

            // Add search term frequency matches
            $titleLower = mb_strtolower($product->title);
            $tags       = array_map('mb_strtolower', (array) ($product->tags ?? []));

            foreach ($searchTerms as $term) {
                $q = mb_strtolower($term->query);
                if (str_contains($titleLower, $q)) {
                    $score += $term->freq * 3;
                }
                foreach ($tags as $tag) {
                    if (str_contains($tag, $q) || str_contains($q, $tag)) {
                        $score += $term->freq;
                    }
                }
            }

            if ($score > 0) {
                $scores[$product->id] = $score;
            }
        }

        arsort($scores);

        // Upsert top 50 into trending_products
        $now = now();
        TrendingProduct::where('period', $period)->delete();

        foreach (array_slice($scores, 0, 50, true) as $productId => $score) {
            TrendingProduct::create([
                'product_id'  => $productId,
                'score'       => $score,
                'period'      => $period,
                'computed_at' => $now,
            ]);
        }

        $this->info('Done. Top ' . count(array_slice($scores, 0, 50)) . ' products saved.');

        return self::SUCCESS;
    }
}
