<?php namespace OFFLINE\Sentry;

use Block;
use Event;
use OFFLINE\Sentry\Classes\Context;
use OFFLINE\Sentry\Classes\ExceptionReporter;
use OFFLINE\Sentry\Classes\SentryLaravelEventHandler;
use OFFLINE\Sentry\Models\Settings;
use Sentry\ClientBuilder;
use Sentry\ClientBuilderInterface;
use Sentry\Integration as SdkIntegration;
use Sentry\Laravel\Integration;
use Sentry\SentrySdk;
use Sentry\State\Hub;
use Sentry\State\HubInterface;
use Sentry\State\Scope;
use System\Classes\PluginBase;
use System\Classes\SettingsManager;
use System\Models\PluginVersion;
use System\Traits\ViewMaker;

class Plugin extends PluginBase
{
    use Context, ViewMaker;

    public static $identifier = 'OFFLINE.Sentry';

    /**
     * Register the Sentry specific ExceptionHandler class.
     *
     * @return void
     */
    public function register()
    {
        if (class_exists('Sentry') === false) {
            class_alias(\OFFLINE\Sentry\Classes\SentryFacade::class, 'Sentry');
        }
    }

    /**
     * Boot the plugin.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->useSentryExceptionHandler()) {
            Event::listen('exception.report', function (\Throwable $exception) {
                (new ExceptionReporter($this->app))->captureException($exception);
            });
        }

        $this->registerSentrySettings();
        $this->rebindSentryWithCustomConfiguration();
        $this->registerSentryEvents();

        try {
            if (Settings::get('log_backend_errors', false)) {
                // Install backend error tracking.
                Block::set('head', $this->makePartial('$/offline/sentry/views/backend_tracking.htm', [
                    'dsn' => Settings::get('dsn')
                ]));
            }
        } catch (\Throwable $e) {
            // Database has not been seeded yet.
        }
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
                'label' => 'Sentry',
                'description' => 'Manage your Sentry error logging settings',
                'category' => SettingsManager::CATEGORY_SYSTEM,
                'icon' => 'icon-bug',
                'class' => Settings::class,
                'order' => 500,
                'keywords' => 'sentry error reporting',
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
                'tab' => 'Sentry',
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
            try {
                return Settings::getConfigArray();
            } catch (\Throwable $e) {
                return [];
            }
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
        $this->app->bind(ClientBuilderInterface::class, function () {
            $basePath = base_path();
	    $pluginVersion = 'unknown';
            try {
                $pluginVersion = PluginVersion::getVersion(self::$identifier) ?: 'unknown';
            } catch (\Throwable $exception) {
                // depending on the context the database connection might not be available yet
                // in this case we don't care about the plugin version.
            }
            $options = [
                'environment' => $this->app->environment(),
                'prefixes' => [$basePath],
                'in_app_exclude' => ["{$basePath}/vendor"],
                'dsn' => Settings::get('dsn'),
            ];

            $clientBuilder = ClientBuilder::create($options);

            // Set the Laravel SDK identifier and version
            $clientBuilder->setSdkIdentifier(self::$identifier);
            $clientBuilder->setSdkVersion($pluginVersion);

            return $clientBuilder;
        });

        /** @var \Sentry\ClientBuilderInterface $clientBuilder */
        $clientBuilder = $this->app->make(ClientBuilderInterface::class);

        $options = $clientBuilder->getOptions();

        $options->setIntegrations(static function (array $integrations) use ($options) {

            $integrations[] = new Integration();

            if ( ! $options->hasDefaultIntegrations()) {
                return $integrations;
            }

            // Remove the default error and fatal exception listeners to let Laravel handle those
            // itself. These event are still bubbling up through the documented changes in the users
            // `ExceptionHandler` of their application or through the log channel integration to Sentry
            return array_filter($integrations,
                static function (SdkIntegration\IntegrationInterface $integration): bool {
                    if ($integration instanceof SdkIntegration\ErrorListenerIntegration) {
                        return false;
                    }

                    if ($integration instanceof SdkIntegration\ExceptionListenerIntegration) {
                        return false;
                    }

                    if ($integration instanceof SdkIntegration\FatalErrorListenerIntegration) {
                        return false;
                    }

                    return true;
                });
        });

        $hub = new Hub($clientBuilder->getClient());
        $hub->configureScope(function (Scope $scope) {
            if ($hostname = Settings::get('name')) {
                $scope->setTag('hostname', $hostname);
            }
        });

        SentrySdk::setCurrentHub($hub);

        $this->app->singleton('sentry', function () use ($hub) {
            return $hub;
        });

        $this->app->alias('sentry', HubInterface::class);
    }

    /**
     * Register the SentryLaravelEventHandler to record breadcrumbs.
     *
     * @return void
     */
    protected function registerSentryEvents()
    {
        $handler = new SentryLaravelEventHandler($this->app->events, $this->app['sentry.config']);
        $handler->subscribe();
    }

    /**
     * Check if the Sentry ExceptionHandler should be used.
     *
     * @return bool
     */
    protected function useSentryExceptionHandler()
    {
        return config('app.debug') !== true || $this->ignoreDebugMode();
    }

    /**
     * Check if Exceptions should even with debug mode enabled should be reported.
     *
     * @return bool
     */
    protected function ignoreDebugMode()
    {
	try {
            return (bool)Settings::get('ignore_debug_mode', false);
	} catch (\Throwable $e) {
	    // If the database hat not been seeded yet, return the default value.
            return false;
	}
    }
}
