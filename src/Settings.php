<?php

declare(strict_types=1);

namespace WPUpSaga\Plugin;

final class Settings
{
    public const OPTION_KEY = 'wpupsaga_settings';

    /**
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'app_url' => '',
            'site_uuid' => '',
            'api_key' => '',
            'paired' => false,
            'paired_at' => '',
            'site_status' => 'pending',
            'last_error' => '',
            'last_delivery_at' => '',
            'last_delivery_error' => '',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function all(): array
    {
        $stored = \get_option(self::OPTION_KEY, []);

        return array_merge(self::defaults(), is_array($stored) ? $stored : []);
    }

    public static function update(array $changes): void
    {
        \update_option(self::OPTION_KEY, array_merge(self::all(), $changes));
    }

    public static function isConfigured(): bool
    {
        $settings = self::all();

        return is_string($settings['app_url'])
            && $settings['app_url'] !== ''
            && is_string($settings['site_uuid'])
            && $settings['site_uuid'] !== ''
            && is_string($settings['api_key'])
            && $settings['api_key'] !== '';
    }
}