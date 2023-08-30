<?php


namespace OFFLINE\Sentry\Classes;


use Backend\Facades\BackendAuth;
use System\Classes\PluginManager;

trait Context
{
    /**
     * Returns the logged in backend user.
     *
     * @return array
     */
    protected function getBackendUser(): array
    {
        if (BackendAuth::check()) {
            return BackendAuth::getUser()->toArray() + ['user_type' => 'backend'];
        }

        return ['id' => null];
    }

    /**
     * Returns the logged in rainlab.user.
     *
     * @return array
     */
    protected function getFrontendUser(): array
    {
        if ($this->rainlabUserInstalled()) {
            $user = Auth::getUser();

            return $user ? $user->toArray() + ['user_type' => 'RainLab.User'] : [];
        }

        return ['id' => null];
    }

    /**
     * Checks if the RainLab.User plugin is installed and enabled.
     *
     * @return bool
     */
    protected function rainlabUserInstalled()
    {
        return PluginManager::instance()->exists('RainLab.User')
            && ! PluginManager::instance()->isDisabled('RainLab.User');
    }
}
