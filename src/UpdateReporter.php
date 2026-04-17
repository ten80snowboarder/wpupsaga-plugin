<?php

declare(strict_types=1);

namespace WPUpSaga\Plugin;

final class UpdateReporter
{
    public function __construct(private readonly ApiClient $client)
    {
    }

    public function register(): void
    {
        \add_action('upgrader_process_complete', [$this, 'handleUpgraderProcessComplete'], 10, 2);
    }

    /**
     * @param object $upgrader
     * @param array<string, mixed> $hookExtra
     */
    public function handleUpgraderProcessComplete(object $upgrader, array $hookExtra): void
    {
        $settings = Settings::all();

        if (!Settings::isConfigured() || empty($settings['paired'])) {
            return;
        }

        if (($hookExtra['action'] ?? '') !== 'update') {
            return;
        }

        $items = $this->buildItems($hookExtra);

        if ($items === []) {
            return;
        }

        $deliveryUuid = \wp_generate_uuid4();
        $payload = [
            'delivery_uuid' => $deliveryUuid,
            'site_uuid' => (string) $settings['site_uuid'],
            'site_url' => \untrailingslashit(\home_url('/')),
            'event_type' => 'updates.completed',
            'event_time_gmt' => \gmdate(DATE_ATOM),
            'update_mode' => \wp_doing_cron() ? 'automatic' : 'manual',
            'items' => $items,
            'environment' => [
                'wp_version' => (string) \get_bloginfo('version'),
                'php_version' => PHP_VERSION,
                'plugin_version' => WPUPSAGA_PLUGIN_VERSION,
            ],
        ];

        $response = $this->client->post('/api/v1/plugin/site-events', $payload, $deliveryUuid);

        if ($response['ok']) {
            Settings::update([
                'last_delivery_at' => \gmdate('Y-m-d H:i:s'),
                'last_delivery_error' => '',
            ]);

            return;
        }

        Settings::update([
            'last_delivery_error' => (string) ($response['body']['message'] ?? $response['error'] ?? 'Event delivery failed.'),
        ]);
    }

    /**
     * @param array<string, mixed> $hookExtra
     * @return list<array<string, mixed>>
     */
    private function buildItems(array $hookExtra): array
    {
        return match ((string) ($hookExtra['type'] ?? '')) {
            'plugin' => $this->buildPluginItems($hookExtra),
            'theme' => $this->buildThemeItems($hookExtra),
            'core' => [$this->buildCoreItem()],
            'translation' => $this->buildTranslationItems($hookExtra),
            default => [],
        };
    }

    /**
     * @param array<string, mixed> $hookExtra
     * @return list<array<string, mixed>>
     */
    private function buildPluginItems(array $hookExtra): array
    {
        if (!\function_exists('get_plugin_data')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $pluginFiles = [];

        if (is_array($hookExtra['plugins'] ?? null)) {
            $pluginFiles = $hookExtra['plugins'];
        } elseif (is_string($hookExtra['plugin'] ?? null)) {
            $pluginFiles = [(string) $hookExtra['plugin']];
        }

        $items = [];

        foreach ($pluginFiles as $pluginFile) {
            if (!is_string($pluginFile) || $pluginFile === '') {
                continue;
            }

            $absolutePath = WP_PLUGIN_DIR . '/' . $pluginFile;
            $pluginData = file_exists($absolutePath) ? \get_plugin_data($absolutePath, false, false) : [];
            $slug = dirname($pluginFile);

            if ($slug === '.' || $slug === '') {
                $slug = basename($pluginFile, '.php');
            }

            $items[] = [
                'event_uuid' => \wp_generate_uuid4(),
                'object_type' => 'plugin',
                'object_slug' => $slug,
                'object_name' => (string) ($pluginData['Name'] ?? $slug),
                'from_version' => null,
                'to_version' => isset($pluginData['Version']) ? (string) $pluginData['Version'] : null,
                'update_status' => 'completed',
            ];
        }

        return $items;
    }

    /**
     * @param array<string, mixed> $hookExtra
     * @return list<array<string, mixed>>
     */
    private function buildThemeItems(array $hookExtra): array
    {
        $themes = [];

        if (is_array($hookExtra['themes'] ?? null)) {
            $themes = $hookExtra['themes'];
        } elseif (is_string($hookExtra['theme'] ?? null)) {
            $themes = [(string) $hookExtra['theme']];
        }

        $items = [];

        foreach ($themes as $stylesheet) {
            if (!is_string($stylesheet) || $stylesheet === '') {
                continue;
            }

            $theme = \wp_get_theme($stylesheet);
            $items[] = [
                'event_uuid' => \wp_generate_uuid4(),
                'object_type' => 'theme',
                'object_slug' => $stylesheet,
                'object_name' => $theme->exists() ? $theme->get('Name') : $stylesheet,
                'from_version' => null,
                'to_version' => $theme->exists() ? $theme->get('Version') : null,
                'update_status' => 'completed',
            ];
        }

        return $items;
    }

    /**
     * @param array<string, mixed> $hookExtra
     * @return list<array<string, mixed>>
     */
    private function buildTranslationItems(array $hookExtra): array
    {
        $translations = is_array($hookExtra['translations'] ?? null) ? $hookExtra['translations'] : [];
        $items = [];

        foreach ($translations as $translation) {
            $data = is_object($translation) ? get_object_vars($translation) : (is_array($translation) ? $translation : []);
            $slug = (string) ($data['slug'] ?? $data['textdomain'] ?? 'translation');
            $locale = (string) ($data['language'] ?? $data['locale'] ?? '');
            $name = trim($slug . ($locale !== '' ? ' (' . $locale . ')' : ''));

            $items[] = [
                'event_uuid' => \wp_generate_uuid4(),
                'object_type' => 'translation',
                'object_slug' => $slug,
                'object_name' => $name !== '' ? $name : 'Translation update',
                'from_version' => null,
                'to_version' => isset($data['version']) ? (string) $data['version'] : null,
                'translation_locale' => $locale !== '' ? $locale : null,
                'update_status' => 'completed',
            ];
        }

        return $items;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildCoreItem(): array
    {
        return [
            'event_uuid' => \wp_generate_uuid4(),
            'object_type' => 'core',
            'object_slug' => 'wordpress',
            'object_name' => 'WordPress Core',
            'from_version' => null,
            'to_version' => (string) \get_bloginfo('version'),
            'update_status' => 'completed',
        ];
    }
}