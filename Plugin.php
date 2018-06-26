<?php namespace OFFLINE\Sentry;

use App;
use OFFLINE\Sentry\Classes\SentryContextMiddleware;
use OFFLINE\Sentry\Models\Settings;
use Sentry\SentryLaravel\SentryLaravel;
use Sentry\SentryLaravel\SentryLaravelEventHandler;
use Sentry\SentryLaravel\SentryLaravelServiceProvider;
use System\Classes\PluginBase;
use System\Classes\SettingsManager;

class Plugin extends PluginBase
{
    /**
     * Register the Sentry specific ExceptionHandler class.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(
            \Illuminate\Contracts\Debug\ExceptionHandler::class,
            \OFFLINE\Sentry\Classes\ExceptionHandler::class
        );
        class_alias(\Sentry\SentryLaravel\SentryFacade::class, 'Sentry');
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
        $this->app->singleton(SentryLaravelServiceProvider::$abstract, function ($app) {
            $userConfig = $app['sentry.config'];
            $basePath   = base_path();

            return SentryLaravel::getClient(array_merge([
                'environment'        => $app->environment(),
                'prefixes'           => [$basePath],
                'app_path'           => $basePath,
                'excluded_app_paths' => [$basePath . '/vendor'],
            ], $userConfig));
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
