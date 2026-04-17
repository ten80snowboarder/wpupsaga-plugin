<?php

declare(strict_types=1);

namespace WPUpSaga\Plugin;

use WPUpSaga\Plugin\Admin\SettingsPage;
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

final class Plugin
{
    private string $pluginFile;

    public function __construct(string $pluginFile)
    {
        $this->pluginFile = $pluginFile;
    }

    public function boot(): void
    {
        $apiClient = new ApiClient();
        $settingsPage = new SettingsPage();
        $settingsPage->register();

        $pairingService = new PairingService($apiClient);
        $pairingService->register();

        $updateReporter = new UpdateReporter($apiClient);
        $updateReporter->register();

        $this->registerUpdateChecker();
    }

    private function registerUpdateChecker(): void
    {
        $updateChecker = PucFactory::buildUpdateChecker(
            'https://github.com/ten80snowboarder/wpupsaga-plugin/',
            $this->pluginFile,
            'wpupsaga'
        );

        $updateChecker->setBranch('main');
        $updateChecker->addFilter('pre_inject_info', [$this, 'normalizeInjectedTestedVersion']);
        $updateChecker->addFilter('pre_inject_update', [$this, 'normalizeInjectedTestedVersion']);
    }

    /**
     * PUC expands "6.8" to a synthetic value like "6.8.999".
     * Strip that display hack back out before WordPress renders update metadata.
     */
    public function normalizeInjectedTestedVersion(object $item): object
    {
        if (isset($item->tested) && is_string($item->tested) && preg_match('/^(\d+\.\d+)\.999$/', $item->tested, $matches)) {
            $item->tested = $matches[1];
        }

        return $item;
    }
}
