<?php

namespace App\Controllers;

use App\Models\PushSubscription;

class PushController
{
    private function json($data, int $statusCode = 200)
    {
        header('Content-Type: application/json');
        http_response_code($statusCode);
        echo json_encode($data);
        exit;
    }

    private function ensureSession()
    {
        if (!isset($_SESSION)) {
            @session_start();
        }
    }

    private function getUserRecipient()
    {
        $this->ensureSession();
        $userId = (int)($_SESSION['user_id'] ?? 0);
        if ($userId <= 0) {
            return null;
        }
        return ['type' => 'user', 'id' => $userId];
    }

    private function getTenantRecipient()
    {
        $this->ensureSession();
        $tenantId = (int)($_SESSION['tenant_id'] ?? 0);
        if ($tenantId <= 0) {
            return null;
        }
        return ['type' => 'tenant', 'id' => $tenantId];
    }

    public function vapidPublicKey()
    {
        try {
            $key = (string)(getenv('VAPID_PUBLIC_KEY') ?: '');
            if ($key === '') {
                $this->json(['success' => false, 'message' => 'Push not configured'], 501);
            }
            $this->json(['success' => true, 'publicKey' => $key]);
        } catch (\Throwable $t) {
            $this->json(['success' => false, 'message' => 'Unable to load key'], 500);
        }
    }

    public function subscribe()
    {
        try {
            if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
                $this->json(['success' => false, 'message' => 'Invalid request'], 405);
            }
            $rec = $this->getUserRecipient();
            if (!$rec) {
                $this->json(['success' => false, 'message' => 'Unauthorized'], 401);
            }

            $raw = file_get_contents('php://input');
            $sub = json_decode((string)$raw, true);
            if (!is_array($sub)) {
                $this->json(['success' => false, 'message' => 'Invalid payload'], 422);
            }

            $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;
            $model = new PushSubscription();
            $model->upsert($rec['type'], (int)$rec['id'], $sub, $ua ? (string)$ua : null);
            $this->json(['success' => true]);
        } catch (\Throwable $t) {
            $this->json(['success' => false, 'message' => 'Unable to subscribe'], 500);
        }
    }

    public function unsubscribe()
    {
        try {
            if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
                $this->json(['success' => false, 'message' => 'Invalid request'], 405);
            }
            $rec = $this->getUserRecipient();
            if (!$rec) {
                $this->json(['success' => false, 'message' => 'Unauthorized'], 401);
            }

            $raw = file_get_contents('php://input');
            $payload = json_decode((string)$raw, true);
            if (!is_array($payload)) {
                $this->json(['success' => false, 'message' => 'Invalid payload'], 422);
            }
            $endpoint = (string)($payload['endpoint'] ?? '');
            if ($endpoint === '') {
                $this->json(['success' => false, 'message' => 'Missing endpoint'], 422);
            }

            $model = new PushSubscription();
            $ok = $model->deleteByEndpoint($rec['type'], (int)$rec['id'], $endpoint);
            $this->json(['success' => true, 'deleted' => $ok]);
        } catch (\Throwable $t) {
            $this->json(['success' => false, 'message' => 'Unable to unsubscribe'], 500);
        }
    }

    public function tenantVapidPublicKey()
    {
        return $this->vapidPublicKey();
    }

    public function tenantSubscribe()
    {
        try {
            if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
                $this->json(['success' => false, 'message' => 'Invalid request'], 405);
            }
            $rec = $this->getTenantRecipient();
            if (!$rec) {
                $this->json(['success' => false, 'message' => 'Unauthorized'], 401);
            }

            $raw = file_get_contents('php://input');
            $sub = json_decode((string)$raw, true);
            if (!is_array($sub)) {
                $this->json(['success' => false, 'message' => 'Invalid payload'], 422);
            }

            $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;
            $model = new PushSubscription();
            $model->upsert($rec['type'], (int)$rec['id'], $sub, $ua ? (string)$ua : null);
            $this->json(['success' => true]);
        } catch (\Throwable $t) {
            $this->json(['success' => false, 'message' => 'Unable to subscribe'], 500);
        }
    }

    public function tenantUnsubscribe()
    {
        try {
            if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
                $this->json(['success' => false, 'message' => 'Invalid request'], 405);
            }
            $rec = $this->getTenantRecipient();
            if (!$rec) {
                $this->json(['success' => false, 'message' => 'Unauthorized'], 401);
            }

            $raw = file_get_contents('php://input');
            $payload = json_decode((string)$raw, true);
            if (!is_array($payload)) {
                $this->json(['success' => false, 'message' => 'Invalid payload'], 422);
            }
            $endpoint = (string)($payload['endpoint'] ?? '');
            if ($endpoint === '') {
                $this->json(['success' => false, 'message' => 'Missing endpoint'], 422);
            }

            $model = new PushSubscription();
            $ok = $model->deleteByEndpoint($rec['type'], (int)$rec['id'], $endpoint);
            $this->json(['success' => true, 'deleted' => $ok]);
        } catch (\Throwable $t) {
            $this->json(['success' => false, 'message' => 'Unable to unsubscribe'], 500);
        }
    }
}
