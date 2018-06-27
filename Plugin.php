<?php namespace OFFLINE\Sentry;

use App;
use OFFLINE\Sentry\Classes\SentryLaravelEventHandler;
use OFFLINE\Sentry\Models\Settings;
use System\Classes\PluginBase;
use System\Classes\PluginManager;
use System\Classes\SettingsManager;
use System\Models\PluginVersion;

class Plugin extends PluginBase
{
    public static $identifier = 'OFFLINE.Sentry';

    /**
     * Register the Sentry specific ExceptionHandler class.
     *
     * @return void
     */
    public function register()
    {
        class_alias(\OFFLINE\Sentry\Classes\SentryFacade::class, 'Sentry');
    }

    /**
     * Boot the plugin.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerSentrySettings();
        $this->rebindSentryWithCustomConfiguration();
        $this->registerSentryEvents();

        $this->app->singleton(
            \Illuminate\Contracts\Debug\ExceptionHandler::class,
            \OFFLINE\Sentry\Classes\ExceptionHandler::class
        );
    }

    /**
     * Register the plugin in the backend settings menu.
     *
     * @return array
     */
    public function registerSettings()
    {
        return [
            'sentry' => [
                'label'       => 'Sentry',
                'description' => 'Manage your Sentry error logging settings',
                'category'    => SettingsManager::CATEGORY_SYSTEM,
                'icon'        => 'icon-bug',
                'class'       => Settings::class,
                'order'       => 500,
                'keywords'    => 'sentry error reporting',
                'permissions' => ['offline.sentry.manage'],
            ],
        ];
    }

    /**
     * Register the plugin's permissions.
     *
     * @return array
     */
    public function registerPermissions()
    {
        return [
            'offline.sentry.manage' => [
                'label' => 'Manage Sentry settings',
                'tab'   => 'Sentry',
                'order' => 200,
            ],
        ];
    }

    /**
     * Register the user's Sentry settings in the service container.
     *
     * @return void
     */
    protected function registerSentrySettings()
    {
        $this->app->singleton('sentry.config', function () {
            return Settings::getConfigArray();
        });
    }

    /**
     * Since we are loading the configuration from the database we need to
     * reinitialize Sentry in the Plugin's boot method. We cannot register it
     * in the register method since the database is not ready yet.
     *
     * @return void
     */
    protected function rebindSentryWithCustomConfiguration()
    {
        $this->app->singleton('sentry', function ($app) {
            $pluginVersion = PluginVersion::getVersion(self::$identifier) ?: 'unknown';
            $basePath      = base_path();
            $config        = [
                'environment'        => $app->environment(),
                'prefixes'           => [$basePath],
                'app_path'           => $basePath,
                'excluded_app_paths' => [$basePath . '/vendor'],
                'sdk'                => [
                    'name'    => self::$identifier,
                    'version' => $pluginVersion,
                ],
            ];

            return new \Raven_Client(array_merge($config, $app['sentry.config']));
        });
    }

    /**
     * Register the SentryLaravelEventHandler to record breadcrumbs.
     *
     * @return void
     */
    protected function registerSentryEvents()
    {
        $handler = new SentryLaravelEventHandler($this->app['sentry'], $this->app['sentry.config']);
        $handler->subscribe($this->app->events);
    }
}
