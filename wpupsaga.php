<?php
/**
 * Plugin Name: WPUpSaga
 * Plugin URI: https://wpupsaga.tobydawes.com
 * Description: Connects a WordPress site to WPUpSaga for site pairing and update reporting.
 * Version: 0.1.2
 * Author: Toby Dawes
 * Requires at least: 6.2
 * Tested up to: 6.8
 * Requires PHP: 7.4
 * Text Domain: wpupsaga
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

define('WPUPSAGA_PLUGIN_FILE', __FILE__);
define('WPUPSAGA_PLUGIN_VERSION', '0.1.2');
define('WPUPSAGA_PLUGIN_BASENAME', \plugin_basename(__FILE__));
define('WPUPSAGA_PLUGIN_DIR', \plugin_dir_path(__FILE__));
define('WPUPSAGA_PLUGIN_URL', \plugin_dir_url(__FILE__));

$autoloadFile = WPUPSAGA_PLUGIN_DIR . 'vendor/autoload.php';

if (! file_exists($autoloadFile)) {
    \add_action('admin_notices', static function (): void {
        if (! \current_user_can('activate_plugins')) {
            return;
        }

        echo '<div class="notice notice-error"><p>';
        echo \esc_html__('WPUpSaga is missing Composer dependencies. Reinstall the plugin package or run Composer before activating it.', 'wpupsaga');
        echo '</p></div>';
    });

    return;
}

require_once $autoloadFile;

\add_action('plugins_loaded', static function (): void {
    $plugin = new \WPUpSaga\Plugin\Plugin(WPUPSAGA_PLUGIN_FILE);
    $plugin->boot();
});
