<?php

declare(strict_types=1);

namespace WPUpSaga\Plugin\Admin;

final class SettingsPage
{
    private const OPTION_KEY = 'wpupsaga_settings';

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
            self::OPTION_KEY,
            [
                'type' => 'array',
                'sanitize_callback' => [$this, 'sanitizeSettings'],
                'default' => [
                    'app_url' => '',
                    'api_key' => '',
                ],
            ]
        );

        \add_settings_section(
            'wpupsaga_connection',
            \__('Connection', 'wpupsaga'),
            function (): void {
                echo '<p>' . \esc_html__('Connect this site to your WPUpSaga account. Pairing and update event delivery will be added next.', 'wpupsaga') . '</p>';
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
            'app_url' => \esc_url_raw((string) ($input['app_url'] ?? '')),
            'api_key' => \sanitize_text_field((string) ($input['api_key'] ?? '')),
        ];
    }

    public function renderPage(): void
    {
        if (! \current_user_can('manage_options')) {
            return;
        }

        echo '<div class="wrap">';
        echo '<h1>' . \esc_html__('WPUpSaga', 'wpupsaga') . '</h1>';
        echo '<form action="options.php" method="post">';
        \settings_fields('wpupsaga');
        \do_settings_sections('wpupsaga');
        \submit_button(\__('Save Settings', 'wpupsaga'));
        echo '</form>';
        echo '</div>';
    }

    public function renderAppUrlField(): void
    {
        $settings = $this->getSettings();

        printf(
            '<input type="url" class="regular-text" name="%1$s[app_url]" value="%2$s" placeholder="https://wpupsaga.tobydawes.com" />',
            \esc_attr(self::OPTION_KEY),
            \esc_attr($settings['app_url'])
        );
    }

    public function renderApiKeyField(): void
    {
        $settings = $this->getSettings();

        printf(
            '<input type="text" class="regular-text" name="%1$s[api_key]" value="%2$s" autocomplete="off" />',
            \esc_attr(self::OPTION_KEY),
            \esc_attr($settings['api_key'])
        );
    }

    /**
     * @return array{app_url: string, api_key: string}
     */
    private function getSettings(): array
    {
        $settings = \get_option(self::OPTION_KEY, []);

        return [
            'app_url' => is_string($settings['app_url'] ?? null) ? $settings['app_url'] : '',
            'api_key' => is_string($settings['api_key'] ?? null) ? $settings['api_key'] : '',
        ];
    }
}
