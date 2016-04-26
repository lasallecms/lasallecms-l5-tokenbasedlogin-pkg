<?php

namespace Lasallecms\Lasallecmstokenbasedlogin\Repositories;

/**
 *
 * Token Based Login package for the LaSalle Content Management System, based on the Laravel 5 Framework
 * Copyright (C) 2015 - 2016  The South LaSalle Trading Corporation
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 *
 * @package    Token Based Login package for the LaSalle Content Management System
 * @link       http://LaSalleCMS.com
 * @copyright  (c) 2015 - 2016, The South LaSalle Trading Corporation
 * @license    http://www.gnu.org/licenses/gpl-3.0.html
 * @author     The South LaSalle Trading Corporation
 * @email      info@southlasalle.com
 *
 */


/* ==================================================================================================================
   This is the first time I've broken up a repository. The user management system is growing, to the point where the
   addition of this Token Based Login feature made the user management package a bit confusing. With more potential
   bloat from the impending granular permissions package, I thought it best to extract this feature now, if for no
   other reason than to "see how it goes". The user management repository that deals with this feature I am extracting
   to this package's repository.


   ================================================================================================================== */


// LaSalle Software
use Lasallecms\Lasallecmsapi\Repositories\BaseRepository;
use Lasallecms\Usermanagement\Models\User;

// Third party classes
use Carbon\Carbon;

/**
 * Class UserTokenbasedloginRepository
 * @package Lasallecms\Lasallecmstokenbasedlogin\Repositories
 */
class UserTokenbasedloginRepository extends BaseRepository
{
    /**
     * Instance of model
     *
     * @var Lasallecms\Lasallecmsapi\Models\User
     */
    protected $model;


    /**
     * Inject the model
     *
     * @param  Lasallecms\Lasallecmsapi\Models\User $model
     */
    public function __construct(User $model) {
        $this->model   = $model;
    }


    /**
     * UPDATE the "users" table with a login token
     *
     * @param  int  $userID    User's ID
     */
    public function createLoginToken($userID) {
        $user = $this->getFind($userID);

        $user->login_token            = hash_hmac('sha256', Str::random(40), 'secret');
        $user->login_token_created_at = Carbon::now();

        return $user->save();
    }

    /**
     * Does a given login token exist in the users table?
     *
     * @param  string  $token
     * @return mixed
     */
    public function isLoginTokenExist($token) {
        return $this->model->where('login_token', $token)->first();
    }

    /**
     * Has a login token expired?
     *
     * @param  object  $user     User object
     * @return bool
     */
    public function isLoginTokenExpired($user) {
        $startTime = strtotime($user->login_token_created_at);
        $now = strtotime(Carbon::now());
        // The time difference is in seconds, we want in minutes
        $timeDiff = ($now - $startTime)/60;
        $minutes2faFormIsLive = config('lasallecmstokenbasedlogin.token_login_minutes_token_is_live');
        if ($timeDiff > $minutes2faFormIsLive) {
            // Login token has expired
            return true;
        }
        return false;
    }

    /**
     * Remove the 'login_token' and 'login_token_created_at' fields.
     *
     * @param  int  $userID   The user's ID
     * @return mixed
     */
    public function deleteUserLoginTokenFields($userID) {
        $user = $this->getFind($userID);

        $user->login_token            = '';
        $user->login_token_created_at = '';

        return $user->save();
    }
}