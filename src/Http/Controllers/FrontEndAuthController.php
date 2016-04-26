<?php

namespace Lasallecms\Lasallecmstokenbasedlogin\Http\Controllers;

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


// LaSalle Software
use Lasallecms\Lasallecmstokenbasedlogin\Repositories\UserTokenbasedloginRepository;
use Lasallecms\Usermanagement\Http\Controllers\Controller;

// Laravel facades
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;


/**
 * Class TokenLoginController
 * @package Lasallecms\Usermanagement\Http\Controllers\Frontendauth
 */
class FrontEndAuthController extends Controller
{
    /**
     * @var Lasallecms\Lasallecmstokenbasedlogin\Repositories\UserTokenbasedloginRepository
     */
    protected $userTokenbasedloginRepository;

    /**
     * The name of the front-end template where the auth views are located
     *
     * @var string
     */
    protected $frontend_template_name;


    /**
     * CreateLoginToken constructor.
     *
     * @param Lasallecms\Lasallecmstokenbasedlogin\Repositories\UserTokenbasedloginRepository
     */
    public function __construct(
        UserRepository                $userRepository,
        UserTokenbasedloginRepository $userTokenbasedloginRepository
    ) {
        $this->userTokenbasedloginRepository = $userTokenbasedloginRepository;
        $this->frontend_template_name        = config('lasallecmsfrontend.frontend_template_name');
    }


    /**
     * @param  string     $token     Token in "users" db table's "token_login" field
     * @return \Illuminate\Http\Response
     */
    public function getLoginToken($token) {

        if (!config('lasallecmstokenbasedlogin.token_login_enable_front_end')) {
            return;
        }

        // Does login token exist?
        if (!$this->userTokenbasedloginRepository->isLoginTokenExist($token)) {
            return view('usermanagement::frontend.'.$this->frontend_template_name.'.login.login', [
                'title' => 'Login',
            ])
                ->withErrors([
                    'Token Based Login' => 'Your token based login failed. Please login with your email and password.'
                ]);
        }

        // Token exists.. let's get the user
        $user = $this->userTokenbasedloginRepository->isLoginTokenExist($token);

        // Has token expired?
        if ($this->userTokenbasedloginRepository->isLoginTokenExpired($user)) {
            return view('usermanagement::frontend.'.$this->frontend_template_name.'.login.login', [
                'title' => 'Login',
            ])
                ->withErrors([
                    'Token Based Login' => 'Your token based login failed. Please login with your email and password.'
                ]);
        }

        // Is user enabled?
        if (!$user->enabled) {
            return view('usermanagement::frontend.'.$this->frontend_template_name.'.login.login', [
                'title' => 'Login',
            ])
                ->withErrors([
                    'Token Based Login' => 'Your token based login failed. Please contact your admin.'
                ]);
        }

        // Update the user's last_login fields
        $this->userTokenbasedloginRepository->updateUserRecordWithLastlogin($user->id);

        // Delete login token. Can use token just once.
        $this->userTokenbasedloginRepository->deleteUserLoginTokenFields($user->id);


        // Manually login user
        Auth::loginUsingId($user->id);


        // Redirect to front-end dashboard
        return redirect()->route(
            config('lasallecmsfrontend.frontend_redirect_to_this_view_when_user_successfully_logged_in_to_front_end')
        );
    }
}