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
    }
}
