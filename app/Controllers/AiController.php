<?php

namespace App\Controllers;

use App\Models\Setting;
use Exception;

class AiController
{
    private $settings;

    public function __construct()
    {
        $this->settings = new Setting();
    }

    public function chat()
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }

        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }

        if (!verify_csrf_token()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
            return;
        }

        $settings = $this->settings->getAllAsAssoc();
        $enabled = ($settings['ai_enabled'] ?? '0') === '1';
        if (!$enabled) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'AI is disabled by the administrator']);
            return;
        }

        $provider = $settings['ai_provider'] ?? 'openai';
        $apiKey = $settings['openai_api_key'] ?? '';
        $model = $settings['openai_model'] ?? 'gpt-4.1-mini';
        $googleApiKey = $settings['google_api_key'] ?? '';
        $googleModel = $settings['google_model'] ?? 'gemini-1.5-flash';
        $systemPrompt = $settings['ai_system_prompt'] ?? 'You are RentSmart Support AI. Help users with property management tasks and app guidance.';

        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        $message = trim($data['message'] ?? '');

        if ($message === '') {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Message is required']);
            return;
        }

        try {
            if ($provider === 'openai') {
                if (empty($apiKey)) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'OpenAI API key is not configured']);
                    return;
                }

                $payload = json_encode([
                    'model' => $model,
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $message],
                    ],
                    'temperature' => 0.2,
                ]);

                $ch = curl_init('https://api.openai.com/v1/chat/completions');
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $apiKey,
                ]);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curlError = curl_error($ch);
                curl_close($ch);

                if ($curlError) {
                    http_response_code(500);
                    echo json_encode(['success' => false, 'message' => $curlError]);
                    return;
                }

                $json = json_decode($response, true);
                if ($httpCode !== 200) {
                    $msg = $json['error']['message'] ?? 'AI request failed';
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => $msg]);
                    return;
                }

                $reply = $json['choices'][0]['message']['content'] ?? '';
                echo json_encode(['success' => true, 'reply' => $reply]);
                return;
            }

            if ($provider === 'google') {
                if (empty($googleApiKey)) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Google API key is not configured']);
                    return;
                }

                $payload = json_encode([
                    'contents' => [
                        ['parts' => [
                            ['text' => $systemPrompt . "\n\nUser: " . $message]
                        ]]
                    ],
                    'generationConfig' => [
                        'temperature' => 0.2,
                        'maxOutputTokens' => 8192,
                    ],
                ]);

                $url = "https://generativelanguage.googleapis.com/v1beta/models/{$googleModel}:generateContent?key={$googleApiKey}";
                $maxRetries = 2;
                $attempt = 0;
                $delay = 500000; // 0.5s initial delay (microseconds)

                while ($attempt <= $maxRetries) {
                    $attempt++;
                    $ch = curl_init($url);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

                    $response = curl_exec($ch);
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    $curlError = curl_error($ch);
                    curl_close($ch);

                    if ($curlError) {
                        http_response_code(500);
                        echo json_encode(['success' => false, 'message' => $curlError]);
                        return;
                    }

                    $json = json_decode($response, true);

                    // Retry on 503/429 (overload/rate limit) with backoff
                    if (($httpCode === 503 || $httpCode === 429) && $attempt <= $maxRetries) {
                        usleep($delay);
                        $delay *= 2; // exponential backoff
                        continue;
                    }

                    if ($httpCode !== 200) {
                        $msg = $json['error']['message'] ?? 'Google AI request failed';
                        // Provide friendlier message for overload
                        if ($httpCode === 503 || $httpCode === 429) {
                            $msg = 'The AI service is temporarily busy. Please try again in a moment.';
                        }
                        http_response_code(400);
                        echo json_encode(['success' => false, 'message' => $msg]);
                        return;
                    }

                    $reply = $json['candidates'][0]['content']['parts'][0]['text'] ?? '';
                    echo json_encode(['success' => true, 'reply' => $reply]);
                    return;
                }

                // All retries exhausted
                http_response_code(503);
                echo json_encode(['success' => false, 'message' => 'The AI service is temporarily busy. Please try again in a moment.']);
                return;
            }

            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Unsupported AI provider']);
            return;
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
