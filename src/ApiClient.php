<?php

declare(strict_types=1);

namespace WPUpSaga\Plugin;

final class ApiClient
{
    /**
     * @param array<string, mixed> $payload
     * @return array{ok:bool,status:int,body:array<string,mixed>,error:string}
     */
    public function post(string $path, array $payload, ?string $idempotencyKey = null): array
    {
        $settings = Settings::all();
        $appUrl = rtrim((string) ($settings['app_url'] ?? ''), '/');
        $apiKey = (string) ($settings['api_key'] ?? '');

        if ($appUrl === '' || $apiKey === '') {
            return [
                'ok' => false,
                'status' => 0,
                'body' => [],
                'error' => 'WPUpSaga is not fully configured yet.',
            ];
        }

        $body = \wp_json_encode($payload);

        if (!is_string($body)) {
            return [
                'ok' => false,
                'status' => 0,
                'body' => [],
                'error' => 'Failed to encode the request payload.',
            ];
        }

        $timestamp = (string) \time();
        $headers = [
            'Authorization' => 'Bearer ' . $apiKey,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'X-WPUS-Timestamp' => $timestamp,
            'X-WPUS-Signature' => hash_hmac('sha256', $body, $apiKey),
        ];

        if (is_string($idempotencyKey) && $idempotencyKey !== '') {
            $headers['Idempotency-Key'] = $idempotencyKey;
        }

        $response = \wp_remote_post($appUrl . $path, [
            'timeout' => 20,
            'headers' => $headers,
            'body' => $body,
            'data_format' => 'body',
        ]);

        if (\is_wp_error($response)) {
            return [
                'ok' => false,
                'status' => 0,
                'body' => [],
                'error' => $response->get_error_message(),
            ];
        }

        $status = (int) \wp_remote_retrieve_response_code($response);
        $responseBody = (string) \wp_remote_retrieve_body($response);
        $decoded = json_decode($responseBody, true);

        return [
            'ok' => $status >= 200 && $status < 300,
            'status' => $status,
            'body' => is_array($decoded) ? $decoded : [],
            'error' => $status >= 200 && $status < 300 ? '' : ($responseBody !== '' ? $responseBody : 'Unexpected API error.'),
        ];
    }
}