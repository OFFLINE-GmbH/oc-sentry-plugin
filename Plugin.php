<?php namespace OFFLINE\Sentry;

use Event;
use Illuminate\Support\Facades\App;
use OFFLINE\Sentry\Classes\Context;
use Sentry\State\Hub;
use Sentry\State\Scope;
use System\Classes\PluginBase;
use System\Classes\UpdateManager;

class Plugin extends PluginBase
{
    use Context;

    public function register()
    {
        Event::listen('exception.report', function (\Exception $e) {
            $config = config('sentry');

            if (!array_get($config, 'environment')) {
                $config['environment'] = App::environment();
            }

            if (!$this->app->bound('sentry')) {
                return;
            }

            /** @var Hub $sentry */
            $sentry = $this->app->get('sentry');

            $sentry->configureScope(function (Scope $scope) {
                if ($user = $this->getBackendUser()) {
                    $scope->setUser($user);
                }

                if ($frontendUser = $this->getFrontendUser()) {
                    $scope->setExtra('RainLab.User', $frontendUser);
                }

                try {
                    $octoberVersion = UpdateManager::instance()->getCurrentVersion();
                } catch (\Throwable $e) {
                    $octoberVersion = 'unknown';
                }

                $scope->setExtra('october_version', $octoberVersion);
            });

            $sentry->captureException($e);
        });
    }
}
