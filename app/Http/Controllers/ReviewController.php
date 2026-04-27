<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\Product;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index()
    {
        $reviews = Review::with('user', 'product')->latest()->paginate(30);
        return view('admin.reviews.index', compact('reviews'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'product_id' => 'required|exists:products,id',
            'rating' => 'required|integer|min:1|max:5',
            'title' => 'required|string|max:255',
            'comment' => 'nullable|string|max:1000',
        ]);

        $review = Review::create([
            'user_id' => null,
            'product_id' => $data['product_id'],
            'order_id' => null,
            'order_item_id' => null,
            'store_id' => null,
            'rating' => $data['rating'],
            'title' => $data['title'],
            'comment' => $data['comment'] ?? null,
            'status' => 'approved',
        ]);

        return redirect()->back()->with('success', __('labels.review_added_successfully'));
    }

    public function approve(int $id)
    {
        $review = Review::find($id);
        if (!$review) return redirect()->back()->with('error', __('labels.review_not_found'));
        $review->status = 'approved';
        $review->save();
        return redirect()->back()->with('success', __('labels.review_updated_successfully'));
    }

    public function reject(int $id)
    {
        $review = Review::find($id);
        if (!$review) return redirect()->back()->with('error', __('labels.review_not_found'));
        $review->status = 'rejected';
        $review->save();
        return redirect()->back()->with('success', __('labels.review_updated_successfully'));
    }

    public function destroy(int $id)
    {
        $review = Review::find($id);
        if (!$review) return redirect()->back()->with('error', __('labels.review_not_found'));
        $review->delete();
        return redirect()->back()->with('success', 'Review deleted successfully.');
    }
}
