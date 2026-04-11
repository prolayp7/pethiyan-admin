<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\UserFcmToken;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Illuminate\Support\Collection;

class FirebaseService
{
    protected ?Messaging $messaging = null;

    public function __construct()
    {
        $path = storage_path('app/private/settings/service-account-file.json');

        if (!$path || !file_exists($path)) {
            Log::info('Firebase service account not found');

            return;
        }

        try {
            $firebase = (new Factory)->withServiceAccount($path);
            $this->messaging = $firebase->createMessaging();
        } catch (\Throwable $e) {
            // Never break page rendering when Firebase config is invalid.
            $this->messaging = null;
            Log::warning('Firebase initialization skipped: ' . $e->getMessage());
        }
    }

    public function sendNotification($token, $title, $body, $image = "", $data = []): array
    {
        if ($this->messaging === null) {
            Log::info('Firebase is not configured');
            return [
                'success' => 0,
                'failure' => 1,
                'error' => 'Firebase is not configured',
            ];
        }

        $notification = Notification::create(title: $title, body: $body, imageUrl: $image);
        $message = CloudMessage::new()
            ->withNotification($notification)
            ->withData($data)
            ->withDefaultSounds()
            ->toToken($token)
            // ->toTopic('...')
            // ->toCondition('...')
        ;

        $this->messaging->send($message);

        return [
            'success' => 1,
            'failure' => 0,
        ];
    }

    /**
     * Send a notification to multiple tokens in chunks
     */
    public function sendBulkNotification(array $tokens, string $title, string $body, string $image = null, array $data = [], int $chunkSize = 50): array
    {
        $results = [
            'success' => 0,
            'failure' => 0,
            'responses' => [],
        ];

        if ($this->messaging === null) {
            Log::info('Firebase is not configured');

            return [
                'success' => 0,
                'failure' => count($tokens),
                'error' => 'Firebase is not configured',
            ];
        }

        $notification = Notification::create(title: $title, body: $body, imageUrl: $image);
        $results['removed_tokens'] = [];
        // Convert to Laravel collection for easy chunking
        Collection::make($tokens)->chunk($chunkSize)->each(function ($chunk) use (&$results, $notification, $data) {
            $message = CloudMessage::new()
                ->withNotification($notification)
                ->withDefaultSounds()
                ->withData($data);

            $multicastResult = $this->messaging->sendMulticast($message, $chunk->toArray());

            // Count results
            $results['success'] += $multicastResult->successes()->count();
            $results['failure'] += $multicastResult->failures()->count();

            // Get invalid tokens
            $invalidTokens = $multicastResult->invalidTokens();

            if (!empty($invalidTokens)) {
                UserFcmToken::whereIn('fcm_token', $invalidTokens)->delete();
                $results['removed_tokens'] = array_merge($results['removed_tokens'], $invalidTokens);
            }
        });
        return $results;
    }
}
