<?php

use CleanSoft\Modules\Core\Users\Models\Contracts\UserModelContract;
use CleanSoft\Modules\Core\Users\Facades\CurrentUserFacade;

if (!function_exists('set_current_logged_user')) {
    /**
     * @param UserModelContract $user
     * @return $this
     */
    function set_current_logged_user($user)
    {
        return CurrentUserFacade::setUser($user);
    }
}

if (!function_exists('get_current_logged_user')) {
    /**
     * @return \CleanSoft\Modules\Core\Users\Models\User|null
     */
    function get_current_logged_user()
    {
        return CurrentUserFacade::getUser();
    }
}

if (!function_exists('get_current_logged_user_id')) {
    /**
     * @return int|null
     */
    function get_current_logged_user_id()
    {
        return get_current_logged_user() ? get_current_logged_user()->id : null;
    }
}
