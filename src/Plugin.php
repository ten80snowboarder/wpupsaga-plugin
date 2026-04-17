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
        $settingsPage = new SettingsPage();
        $settingsPage->register();

        $this->registerUpdateChecker();
    }

    private function registerUpdateChecker(): void
    {
        $updateChecker = PucFactory::buildUpdateChecker(
            'https://github.com/ten80snowboarder/wpupsaga-plugin/',
            $this->pluginFile,
            'wpupsaga-plugin'
        );

        $updateChecker->setBranch('main');
    }
}
