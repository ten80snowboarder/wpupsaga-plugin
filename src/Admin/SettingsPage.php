<?php

declare(strict_types=1);

namespace WPUpSaga\Plugin\Admin;

use WPUpSaga\Plugin\PairingService;
use WPUpSaga\Plugin\Settings;

final class SettingsPage
{
    public function register(): void
    {
        \add_action('admin_menu', [$this, 'addMenuPage']);
        \add_action('admin_init', [$this, 'registerSettings']);
    }

    public function addMenuPage(): void
    {
        \add_options_page(
            \__('WPUpSaga', 'wpupsaga'),
            \__('WPUpSaga', 'wpupsaga'),
            'manage_options',
            'wpupsaga',
            [$this, 'renderPage']
        );
    }

    public function registerSettings(): void
    {
        \register_setting(
            'wpupsaga',
            Settings::OPTION_KEY,
            [
                'type' => 'array',
                'sanitize_callback' => [$this, 'sanitizeSettings'],
                'default' => Settings::defaults(),
            ]
        );

        \add_settings_section(
            'wpupsaga_connection',
            \__('Connection', 'wpupsaga'),
            function (): void {
                echo '<p>' . \esc_html__('Connect this site to your WPUpSaga account, pair it once, and then let the plugin report completed update runs automatically.', 'wpupsaga') . '</p>';
            },
            'wpupsaga'
        );

        \add_settings_field(
            'wpupsaga_app_url',
            \__('App URL', 'wpupsaga'),
            [$this, 'renderAppUrlField'],
            'wpupsaga',
            'wpupsaga_connection'
        );

        \add_settings_field(
            'wpupsaga_site_uuid',
            \__('Site UUID', 'wpupsaga'),
            [$this, 'renderSiteUuidField'],
            'wpupsaga',
            'wpupsaga_connection'
        );

        \add_settings_field(
            'wpupsaga_api_key',
            \__('API Key', 'wpupsaga'),
            [$this, 'renderApiKeyField'],
            'wpupsaga',
            'wpupsaga_connection'
        );
    }

    public function sanitizeSettings(array $input): array
    {
        return [
            ...Settings::defaults(),
            'app_url' => \esc_url_raw((string) ($input['app_url'] ?? '')),
            'site_uuid' => \sanitize_text_field((string) ($input['site_uuid'] ?? '')),
            'api_key' => \sanitize_text_field((string) ($input['api_key'] ?? '')),
            'paired' => !empty($input['paired']),
            'paired_at' => \sanitize_text_field((string) ($input['paired_at'] ?? '')),
            'site_status' => \sanitize_text_field((string) ($input['site_status'] ?? 'pending')),
            'last_error' => \sanitize_text_field((string) ($input['last_error'] ?? '')),
            'last_delivery_at' => \sanitize_text_field((string) ($input['last_delivery_at'] ?? '')),
            'last_delivery_error' => \sanitize_text_field((string) ($input['last_delivery_error'] ?? '')),
        ];
    }

    public function renderPage(): void
    {
        if (! \current_user_can('manage_options')) {
            return;
        }

        $notice = PairingService::pullNotice();
        $settings = $this->getSettings();

        echo '<div class="wrap">';
        echo '<h1>' . \esc_html__('WPUpSaga', 'wpupsaga') . '</h1>';

        if (is_array($notice)) {
            printf(
                '<div class="notice notice-%1$s"><p>%2$s</p></div>',
                \esc_attr($notice['type'] === 'success' ? 'success' : 'error'),
                \esc_html((string) ($notice['message'] ?? ''))
            );
        }

        echo '<form action="options.php" method="post">';
        \settings_fields('wpupsaga');
        \do_settings_sections('wpupsaga');
        \submit_button(\__('Save Settings', 'wpupsaga'));
        echo '</form>';

        echo '<hr />';
        echo '<h2>' . \esc_html__('Pairing status', 'wpupsaga') . '</h2>';
        echo '<p>' . \esc_html__('Save the connection settings first, then pair this site with the WPUpSaga app.', 'wpupsaga') . '</p>';
        echo '<ul style="list-style:disc;padding-left:1.25rem">';
        echo '<li><strong>' . \esc_html__('Status', 'wpupsaga') . ':</strong> ' . \esc_html(!empty($settings['paired']) ? 'Paired' : 'Not paired') . '</li>';
        echo '<li><strong>' . \esc_html__('Remote state', 'wpupsaga') . ':</strong> ' . \esc_html((string) ($settings['site_status'] ?: 'pending')) . '</li>';
        echo '<li><strong>' . \esc_html__('Last paired at', 'wpupsaga') . ':</strong> ' . \esc_html($this->formatSiteLocalTime($settings['paired_at'] ?? '', 'Never')) . '</li>';
        echo '<li><strong>' . \esc_html__('Last delivery', 'wpupsaga') . ':</strong> ' . \esc_html($this->formatSiteLocalTime($settings['last_delivery_at'] ?? '', 'No deliveries yet')) . '</li>';
        echo '</ul>';

        if ((string) ($settings['last_error'] ?? '') !== '') {
            echo '<p><strong>' . \esc_html__('Last pairing error', 'wpupsaga') . ':</strong> ' . \esc_html((string) $settings['last_error']) . '</p>';
        }

        if ((string) ($settings['last_delivery_error'] ?? '') !== '') {
            echo '<p><strong>' . \esc_html__('Last delivery error', 'wpupsaga') . ':</strong> ' . \esc_html((string) $settings['last_delivery_error']) . '</p>';
        }

        if (!Settings::isConfigured()) {
            echo '<p>' . \esc_html__('Save the App URL, Site UUID, and API key first. Pairing validation happens after you click the button.', 'wpupsaga') . '</p>';
        }

        echo '<form action="' . \esc_url(\admin_url('admin-post.php')) . '" method="post">';
        echo '<input type="hidden" name="action" value="wpupsaga_pair_site" />';
        \wp_nonce_field('wpupsaga_pair_site');
        \submit_button(\__('Pair Site', 'wpupsaga'), 'secondary', 'submit', false);
        echo '</form>';
        echo '</div>';
    }

    public function renderAppUrlField(): void
    {
        $settings = $this->getSettings();

        printf(
            '<input type="url" class="regular-text" name="%1$s[app_url]" value="%2$s" placeholder="https://wpupsaga.tobydawes.com" />',
            \esc_attr(Settings::OPTION_KEY),
            \esc_attr($settings['app_url'])
        );
    }

    public function renderSiteUuidField(): void
    {
        $settings = $this->getSettings();

        printf(
            '<input type="text" class="regular-text" name="%1$s[site_uuid]" value="%2$s" autocomplete="off" />',
            \esc_attr(Settings::OPTION_KEY),
            \esc_attr($settings['site_uuid'])
        );
    }

    public function renderApiKeyField(): void
    {
        $settings = $this->getSettings();

        printf(
            '<input type="text" class="regular-text" name="%1$s[api_key]" value="%2$s" autocomplete="off" />',
            \esc_attr(Settings::OPTION_KEY),
            \esc_attr($settings['api_key'])
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function getSettings(): array
    {
        return Settings::all();
    }

    private function formatSiteLocalTime(mixed $value, string $fallback): string
    {
        if (!is_string($value) || trim($value) === '') {
            return $fallback;
        }

        $timestamp = strtotime($value . ' UTC');

        if ($timestamp === false) {
            return $fallback;
        }

        return \wp_date(
            trim((string) \get_option('date_format', 'F j, Y')) . ' ' . trim((string) \get_option('time_format', 'g:i a')),
            $timestamp
        );
    }
}
