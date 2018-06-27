<?php

namespace OFFLINE\Sentry\Models;

use Model;

class Settings extends Model
{
    public $implement = ['System.Behaviors.SettingsModel'];
    public $settingsCode = 'offline_sentry_settings';
    public $settingsFields = 'fields.yaml';

    public static function getConfigArray()
    {
        $defaults = [
            'dsn'                 => null,
            'timeout'             => 2,
            'name'                => null,
            'excluded_exceptions' => [],
        ];

        $settings = self::where('item', 'offline_sentry_settings')->first(['value']);
        $settings = $settings ? $settings->toArray() : [];
        unset($settings['value']);

        return array_filter(array_merge($defaults, $settings));
    }
}