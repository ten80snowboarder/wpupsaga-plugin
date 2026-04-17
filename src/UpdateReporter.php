<?php

declare(strict_types=1);

namespace WPUpSaga\Plugin;

final class UpdateReporter
{
    /**
     * @var array<string, array<string, string>>
     */
    private array $preUpdateVersions = [
        'plugin' => [],
        'theme' => [],
        'core' => [],
    ];

    public function __construct(private readonly ApiClient $client)
    {
    }

    public function register(): void
    {
        \add_filter('upgrader_pre_install', [$this, 'captureVersionsBeforeInstall'], 10, 2);
        \add_action('upgrader_process_complete', [$this, 'handleUpgraderProcessComplete'], 10, 2);
    }

    /**
     * @param mixed $response
     * @param array<string, mixed> $hookExtra
     * @return mixed
     */
    public function captureVersionsBeforeInstall(mixed $response, array $hookExtra): mixed
    {
        if (($hookExtra['action'] ?? '') !== 'update') {
            return $response;
        }

        switch ((string) ($hookExtra['type'] ?? '')) {
            case 'plugin':
                foreach ($this->extractPluginFiles($hookExtra) as $pluginFile) {
                    $version = $this->readInstalledPluginVersion($pluginFile);

                    if ($version !== null) {
                        $this->preUpdateVersions['plugin'][$pluginFile] = $version;
                    }
                }
                break;

            case 'theme':
                foreach ($this->extractThemeStylesheets($hookExtra) as $stylesheet) {
                    $version = $this->readInstalledThemeVersion($stylesheet);

                    if ($version !== null) {
                        $this->preUpdateVersions['theme'][$stylesheet] = $version;
                    }
                }
                break;

            case 'core':
                $currentCoreVersion = (string) \get_bloginfo('version');

                if ($currentCoreVersion !== '') {
                    $this->preUpdateVersions['core']['wordpress'] = $currentCoreVersion;
                }
                break;
        }

        return $response;
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
        $pluginFiles = $this->extractPluginFiles($hookExtra);

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
                'from_version' => $this->resolvePluginFromVersion($pluginFile),
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
        $themes = $this->extractThemeStylesheets($hookExtra);

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
                'from_version' => $this->resolveThemeFromVersion($stylesheet),
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
            'from_version' => $this->preUpdateVersions['core']['wordpress'] ?? null,
            'to_version' => (string) \get_bloginfo('version'),
            'update_status' => 'completed',
        ];
    }

    /**
     * @param array<string, mixed> $hookExtra
     * @return list<string>
     */
    private function extractPluginFiles(array $hookExtra): array
    {
        $pluginFiles = [];

        if (is_array($hookExtra['plugins'] ?? null)) {
            $pluginFiles = $hookExtra['plugins'];
        } elseif (is_string($hookExtra['plugin'] ?? null)) {
            $pluginFiles = [(string) $hookExtra['plugin']];
        }

        return array_values(array_filter($pluginFiles, static fn (mixed $pluginFile): bool => is_string($pluginFile) && $pluginFile !== ''));
    }

    /**
     * @param array<string, mixed> $hookExtra
     * @return list<string>
     */
    private function extractThemeStylesheets(array $hookExtra): array
    {
        $themes = [];

        if (is_array($hookExtra['themes'] ?? null)) {
            $themes = $hookExtra['themes'];
        } elseif (is_string($hookExtra['theme'] ?? null)) {
            $themes = [(string) $hookExtra['theme']];
        }

        return array_values(array_filter($themes, static fn (mixed $stylesheet): bool => is_string($stylesheet) && $stylesheet !== ''));
    }

    private function readInstalledPluginVersion(string $pluginFile): ?string
    {
        if (!\function_exists('get_plugin_data')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $absolutePath = WP_PLUGIN_DIR . '/' . $pluginFile;

        if (!file_exists($absolutePath)) {
            return null;
        }

        $pluginData = \get_plugin_data($absolutePath, false, false);
        $version = isset($pluginData['Version']) ? trim((string) $pluginData['Version']) : '';

        return $version !== '' ? $version : null;
    }

    private function readInstalledThemeVersion(string $stylesheet): ?string
    {
        $theme = \wp_get_theme($stylesheet);

        if (! $theme->exists()) {
            return null;
        }

        $version = trim((string) $theme->get('Version'));

        return $version !== '' ? $version : null;
    }

    private function resolvePluginFromVersion(string $pluginFile): ?string
    {
        if (isset($this->preUpdateVersions['plugin'][$pluginFile])) {
            return $this->preUpdateVersions['plugin'][$pluginFile];
        }

        $updates = \get_site_transient('update_plugins');

        if (is_object($updates) && isset($updates->checked) && is_array($updates->checked)) {
            $checkedVersion = trim((string) ($updates->checked[$pluginFile] ?? ''));

            if ($checkedVersion !== '') {
                return $checkedVersion;
            }
        }

        return null;
    }

    private function resolveThemeFromVersion(string $stylesheet): ?string
    {
        if (isset($this->preUpdateVersions['theme'][$stylesheet])) {
            return $this->preUpdateVersions['theme'][$stylesheet];
        }

        $updates = \get_site_transient('update_themes');

        if (is_object($updates) && isset($updates->checked) && is_array($updates->checked)) {
            $checkedVersion = trim((string) ($updates->checked[$stylesheet] ?? ''));

            if ($checkedVersion !== '') {
                return $checkedVersion;
            }
        }

        return null;
    }
}