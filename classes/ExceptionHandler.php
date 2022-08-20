<?php

namespace OFFLINE\Sentry\Classes;

use OFFLINE\Sentry\Models\Settings;
use Sentry\State\HubInterface;
use Sentry\State\Scope;

class ExceptionHandler extends \October\Rain\Foundation\Exception\Handler
{
    use Context;

    public function report(\Throwable $exception)
    {
        if ($this->reportToSentry($exception)) {
            /** @var \Illuminate\Foundation\Application $app */
            $app = app();
            /** @var HubInterface $sentry */
            $sentry = $app['sentry'];

            $sentry->configureScope(function(Scope $scope) {
                [$userContext, $extraUserContext] = $this->getUserContext();

                $extraContext = $this->getExtraContext($extraUserContext);

                $scope->setUser($userContext);
                $scope->setExtras($extraContext);
            });

            $sentry->captureException($exception);
        }

        parent::report($exception);
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
        return app()->bound('sentry') && $this->shouldReport($exception);
    }


    /**
     * Determine if the exception is in the "do not report" list.
     *
     * @param  \Throwable  $e
     * @return bool
     */
    protected function shouldntReport(\Throwable $e)
    {
        $excluded = Settings::get('excluded_exceptions', []);

        if (in_array(get_class($e), $excluded, true)) {
            return true;
        }

        return parent::shouldntReport($e);
    }

}
