<?php

namespace App\Services;

use App\Models\PushSubscription;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class WebPushService
{
    private function isConfigured(): bool
    {
        return (string)(getenv('VAPID_PUBLIC_KEY') ?: '') !== ''
            && (string)(getenv('VAPID_PRIVATE_KEY') ?: '') !== '';
    }

    public function sendToRecipient(string $recipientType, int $recipientId, array $payload): void
    {
        if (!$this->isConfigured()) {
            return;
        }

        $publicKey = (string)(getenv('VAPID_PUBLIC_KEY') ?: '');
        $privateKey = (string)(getenv('VAPID_PRIVATE_KEY') ?: '');
        $subject = (string)(getenv('VAPID_SUBJECT') ?: 'mailto:support@rentsmart.co.ke');

        $auth = [
            'VAPID' => [
                'subject' => $subject,
                'publicKey' => $publicKey,
                'privateKey' => $privateKey,
            ]
        ];

        $webPush = new WebPush($auth);

        $subModel = new PushSubscription();
        $subs = $subModel->listForRecipient($recipientType, $recipientId);
        if (empty($subs)) {
            return;
        }

        $jsonPayload = json_encode($payload);

        foreach ($subs as $row) {
            try {
                $subscription = Subscription::create([
                    'endpoint' => (string)($row['endpoint'] ?? ''),
                    'publicKey' => (string)($row['p256dh'] ?? ''),
                    'authToken' => (string)($row['auth'] ?? ''),
                    'contentEncoding' => (string)($row['content_encoding'] ?? 'aesgcm'),
                ]);

                $webPush->queueNotification($subscription, $jsonPayload);
            } catch (\Throwable $t) {
            }
        }

        try {
            foreach ($webPush->flush() as $report) {
                try {
                    if (method_exists($report, 'isSubscriptionExpired') && $report->isSubscriptionExpired()) {
                        $endpoint = '';
                        try {
                            $endpoint = $report->getRequest()->getUri()->__toString();
                        } catch (\Throwable $t) {
                        }
                        if ($endpoint !== '') {
                            try {
                                $subModel->deleteByEndpoint($recipientType, $recipientId, $endpoint);
                            } catch (\Throwable $t) {
                            }
                        }
                    }
                } catch (\Throwable $t) {
                }
            }
        } catch (\Throwable $t) {
        }
    }
}
