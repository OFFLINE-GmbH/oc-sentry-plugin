<?php


namespace OFFLINE\Sentry\Classes;


use Backend\Facades\BackendAuth;
use System\Classes\PluginManager;

trait Context
{
    /**
     * Returns the logged in backend user.
     */
    protected function getBackendUser()
    {
        if (BackendAuth::check()) {
            return BackendAuth::getUser()->toArray() + ['user_type' => 'backend'];
        }

        return null;
    }

    /**
     * Returns the logged in rainlab.user.
     */
    protected function getFrontendUser()
    {
        if ($this->rainlabUserInstalled()) {
            $user = Auth::getUser();

            return $user ? $user->toArray() + ['user_type' => 'RainLab.User'] : null;
        }

        return null;
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
