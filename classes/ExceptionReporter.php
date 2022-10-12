<?php

namespace OFFLINE\Sentry\Classes;

use OFFLINE\Sentry\Models\Settings;
use Sentry\State\HubInterface;
use Sentry\State\Scope;

class ExceptionReporter
{
    use Context;

    /**
     * The application instance.
     *
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected $app;

    public function __construct(\Illuminate\Contracts\Foundation\Application $app)
    {
        $this->app = $app;
    }

    public function captureException(\Throwable $exception)
    {
        if (!$this->reportToSentry($exception)) {
            return;
        }

        /** @var HubInterface $sentry */
        $sentry = $this->app->get('sentry');

        $sentry->configureScope(function (Scope $scope) {
            [$userContext, $extraUserContext] = $this->getUserContext();

            $extraContext = $this->getExtraContext($extraUserContext);

            $scope->setUser($userContext);
            $scope->setExtras($extraContext);
        });

        $sentry->captureException($exception);
    }

    /**
     * Check if this exception should be reported to Sentry.
     *
     * @param \Throwable $exception
     *
     * @return bool
     */
    protected function reportToSentry(\Throwable $exception)
    {
        return $this->app->bound('sentry') && !$this->shouldntReport($exception);
    }


    /**
     * Determine if the exception is in the "do not report" list.
     *
     * @param \Throwable $e
     * @return bool
     */
    protected function shouldntReport(\Throwable $e)
    {
        $excluded = (array)Settings::get('excluded_exceptions', []);

        return in_array(get_class($e), $excluded, true);
    }

}
