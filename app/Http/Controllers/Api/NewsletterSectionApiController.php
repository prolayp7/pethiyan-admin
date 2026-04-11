<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\NewsletterSectionController;
use Illuminate\Http\JsonResponse;

class NewsletterSectionApiController extends Controller
{
    public function index(): JsonResponse
    {
        $settings = NewsletterSectionController::getSettings();
        return response()->json($settings);
    }
}
