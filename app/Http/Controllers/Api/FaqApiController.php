<?php

namespace App\Http\Controllers\Api;

use App\Enums\ActiveInactiveStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\FaqResource;
use App\Models\Faq;
use App\Models\FaqCategory;
use App\Types\Api\ApiResponseType;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

#[Group('FAQs')]
class FaqApiController extends Controller
{
    /**
     * Get all active FAQs with pagination and search.
     */
    #[QueryParameter('page', description: 'Page number for pagination.', type: 'int', default: 1, example: 1)]
    #[QueryParameter('per_page', description: 'Number of items per page.', type: 'int', default: 15, example: 15)]
    #[QueryParameter('search', description: 'Search term to filter FAQs by question or answer.', type: 'string', example: 'delivery')]
    public function index(Request $request): JsonResponse
    {
        $perPage    = $request->input('per_page', 15);
        $searchTerm = $request->input('search');

        $query = Faq::where('status', ActiveInactiveStatusEnum::ACTIVE());

        if ($searchTerm) {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('question', 'LIKE', '%' . $searchTerm . '%')
                  ->orWhere('answer', 'LIKE', '%' . $searchTerm . '%');
            });
        }

        $faqs = $query->orderBy('sort_order')->orderBy('id')->paginate($perPage);

        $faqs->getCollection()->transform(fn($faq) => new FaqResource($faq));

        return ApiResponseType::sendJsonResponse(
            success: true,
            message: __('labels.faqs_fetched_successfully'),
            data: $faqs
        );
    }

    /**
     * Get a specific FAQ by ID.
     */
    #[QueryParameter('id', description: 'FAQ ID.', type: 'int', example: 1)]
    public function show($id): JsonResponse
    {
        $faq = Faq::where('status', true)->find($id);

        if (!$faq) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: __('labels.faq_not_found'),
                data: []
            );
        }

        return ApiResponseType::sendJsonResponse(
            success: true,
            message: __('labels.faq_fetched_successfully'),
            data: new FaqResource($faq)
        );
    }

    /**
     * Get active FAQs grouped by category — used by the frontend /faq page.
     *
     * Returns an array of category objects each containing an `items` array.
     * Categories with no active FAQs are omitted.
     * FAQs without a category are appended as a "General" group at the end.
     */
    public function grouped(): JsonResponse
    {
        // 1. Load active categories with their active FAQs (ordered by sort_order)
        $categories = FaqCategory::where('status', 'active')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->with([
                'activeFaqs' => fn($q) => $q->select('id', 'faq_category_id', 'question', 'answer', 'sort_order'),
            ])
            ->get();

        $grouped = $categories
            ->filter(fn($cat) => $cat->activeFaqs->isNotEmpty())
            ->map(fn($cat) => [
                'id'         => $cat->id,
                'name'       => $cat->name,
                'icon'       => $cat->icon ?? '',
                'sort_order' => $cat->sort_order,
                'items'      => $cat->activeFaqs->map(fn($faq) => [
                    'id'       => $faq->id,
                    'question' => $faq->question,
                    'answer'   => $faq->answer,
                ])->values(),
            ])
            ->values();

        // 2. Append uncategorised FAQs as a "General" group if any exist
        $uncategorised = Faq::where('status', 'active')
            ->whereNull('faq_category_id')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->select('id', 'question', 'answer', 'sort_order')
            ->get();

        if ($uncategorised->isNotEmpty()) {
            $grouped->push([
                'id'         => null,
                'name'       => 'General',
                'icon'       => '❓',
                'sort_order' => 9999,
                'items'      => $uncategorised->map(fn($faq) => [
                    'id'       => $faq->id,
                    'question' => $faq->question,
                    'answer'   => $faq->answer,
                ])->values(),
            ]);
        }

        return ApiResponseType::sendJsonResponse(
            success: true,
            message: 'FAQ sections fetched successfully.',
            data: $grouped
        );
    }
}
