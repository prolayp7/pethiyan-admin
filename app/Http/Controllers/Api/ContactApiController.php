<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\ContactEnquiryMail;
use App\Models\Enquiry;
use App\Services\EmailService;
use App\Services\SettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ContactApiController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'    => 'required|string|max:255',
            'phone'   => 'required|string|max:20',
            'email'   => 'nullable|email|max:255',
            'subject' => 'nullable|string|max:255',
            'message' => 'required|string|max:5000',
        ]);

        $enquiry = Enquiry::create([
            'type'    => 'contact',
            'name'    => $validated['name'],
            'phone'   => $validated['phone'],
            'email'   => $validated['email'] ?? null,
            'subject' => $validated['subject'] ?? null,
            'message' => $validated['message'],
            'status'  => 'unread',
        ]);

        $this->notifyAdmin($enquiry);

        return response()->json(['message' => 'Message received. We will get back to you within 24 hours.'], 201);
    }

    private function notifyAdmin(Enquiry $enquiry): void
    {
        try {
            $systemSettings = app(SettingService::class)
                ->getSettingByVariable('system')
                ?->toArray(new Request())['value'] ?? [];

            $supportEmail = trim($systemSettings['sellerSupportEmail'] ?? '');

            if (!$supportEmail) {
                Log::warning('[ContactApiController] sellerSupportEmail not configured — skipping notification.');
                return;
            }

            app(EmailService::class)->send(new ContactEnquiryMail($enquiry), $supportEmail);
        } catch (\Throwable $e) {
            Log::error('[ContactApiController] Failed to send enquiry notification: ' . $e->getMessage());
        }
    }
}
