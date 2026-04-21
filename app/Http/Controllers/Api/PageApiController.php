<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\Request;

class PageApiController extends Controller
{
    public function show($slug)
    {
        $page = Page::where('slug', $slug)->firstOrFail();

        return response()->json([
            'slug' => $page->slug,
            'title' => $page->title,
            'content_blocks' => $page->content_blocks,
            'content' => $page->content,
        ]);
    }
}
