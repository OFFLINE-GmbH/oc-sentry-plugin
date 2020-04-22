<?php

namespace OFFLINE\Sentry\Classes;

use Sentry\State\HubInterface;
use Sentry\State\Scope;

class ExceptionHandler extends \October\Rain\Foundation\Exception\Handler
{
    use Context;

    public function report(\Exception $exception)
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
     * @param \Exception $exception
     *
     * @return bool
     */
    protected function reportToSentry(\Exception $exception)
    {
        return app()->bound('sentry') && $this->shouldReport($exception);
    }

}