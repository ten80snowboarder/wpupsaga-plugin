<?php

declare(strict_types=1);

namespace WPUpSaga\Plugin;

final class PairingService
{
    private const NOTICE_KEY = 'wpupsaga_admin_notice';

    public function __construct(private readonly ApiClient $client)
    {
    }

    public function register(): void
    {
        \add_action('admin_post_wpupsaga_pair_site', [$this, 'handlePairRequest']);
    }

    public function handlePairRequest(): void
    {
        if (! \current_user_can('manage_options')) {
            \wp_die(\esc_html__('You are not allowed to pair this site.', 'wpupsaga'), 403);
        }

        \check_admin_referer('wpupsaga_pair_site');

        if (!Settings::isConfigured()) {
            Settings::update([
                'paired' => false,
                'last_error' => 'App URL, Site UUID, and API key are all required before pairing.',
            ]);
            self::setNotice('error', 'Fill in the App URL, Site UUID, and API key before pairing.');
            $this->redirectToSettings();
        }

        $payload = [
            'site_uuid' => (string) Settings::all()['site_uuid'],
            'site_url' => \untrailingslashit(\home_url('/')),
            'site_name' => \get_bloginfo('name'),
            'environment' => [
                'wp_version' => (string) \get_bloginfo('version'),
                'php_version' => PHP_VERSION,
                'plugin_version' => WPUPSAGA_PLUGIN_VERSION,
            ],
        ];

        $response = $this->client->post('/api/v1/plugin/pair', $payload, (string) Settings::all()['site_uuid'] . ':pair');

        if (! $response['ok'] || ! (($response['body']['paired'] ?? false) === true)) {
            $message = $response['body']['message'] ?? $response['error'] ?? 'Pairing failed.';
            Settings::update([
                'paired' => false,
                'last_error' => (string) $message,
            ]);
            self::setNotice('error', 'Pairing failed: ' . (string) $message);
            $this->redirectToSettings();
        }

        Settings::update([
            'paired' => true,
            'paired_at' => \gmdate('Y-m-d H:i:s'),
            'site_status' => (string) ($response['body']['site_status'] ?? 'active'),
            'last_error' => '',
        ]);
        self::setNotice('success', 'Site paired successfully. WPUpSaga can now report completed update runs.');

        $this->redirectToSettings();
    }

    /**
     * @return array{type:string,message:string}|null
     */
    public static function pullNotice(): ?array
    {
        $notice = \get_transient(self::NOTICE_KEY);
        \delete_transient(self::NOTICE_KEY);

        return is_array($notice) ? $notice : null;
    }

    private static function setNotice(string $type, string $message): void
    {
        \set_transient(self::NOTICE_KEY, [
            'type' => $type,
            'message' => $message,
        ], 60);
    }

    private function redirectToSettings(): void
    {
        \wp_safe_redirect(\admin_url('options-general.php?page=wpupsaga'));
        exit;
    }
}