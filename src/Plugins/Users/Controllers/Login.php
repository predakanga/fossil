<?php

/**
 * Copyright (c) 2011, predakanga
 * All rights reserved.
 * 
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *     * Redistributions of source code must retain the above copyright
 *       notice, this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *     * Neither the name of the <organization> nor the
 *       names of its contributors may be used to endorse or promote products
 *       derived from this software without specific prior written permission.
 * 
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL <COPYRIGHT HOLDER> BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 * 
 * @author predakanga
 * @since 0.1
 * @category Fossil Plugins
 * @package Users
 * @subpackage Controllers
 * @license https://github.com/predakanga/Fossil/blob/master/LICENSE.txt New BSD License
 */

namespace Fossil\Plugins\Users\Controllers;

use \Fossil\OM,
    \Fossil\Plugins\Users\Models\User as UserModel,
    \Fossil\Plugins\Users\Annotations\Compilation\RequireRole,
    \Fossil\Plugins\Users\Forms\Login as LoginForm,
    \Fossil\Plugins\Users\Forms\Signup as SignupForm;

/**
 * Description of login
 *
 * @author predakanga
 */
class Login extends \Fossil\Controllers\AutoController {
    /**
     * @F:Inject(type="Session", lazy=true)
     * @var Fossil\Session\Session
     */
    protected $session;
    
    public function indexAction() {
        return "login";
    }
    
    public function runLogin(LoginForm $loginForm) {
        if($loginForm->isSubmitted()) {
            $user = UserModel::findOneBy($this->container, array('name' => $loginForm->user));
            if(!$user || !$user->verifyPassword($loginForm->pass)) {
                return $this->templateResponse("fossil:login", array('error' => 'Invalid user/pass'));
            }
            
            $this->session->get("FossilAuth")->user = $user;
            // TODO: Set cookie if staySignedIn
            return $this->redirectResponse("?");
        }
        
        return $this->templateResponse("fossil:login", array());
    }
    
    public function runLogout() {
        // TODO: Invalidate the cookie
        $this->session->get("FossilAuth")->wipe();
        
        return $this->redirectResponse("?");
    }
    
    public function runSignup(SignupForm $signupForm) {
        if($signupForm->isSubmitted() && $signupForm->isValid()) {
            $user = UserModel::findOneBy($this->container, array('name' => $signupForm->name));
            if($user) {
                return OM::obj("Responses", "Template")->create("fossil:signup",
                                                                array('error' => 'Username already in use'));
            }
            $user = $this->createUser();
            $user->name = $signupForm->name;
            $user->password = $signupForm->pass;
            $user->email = $signupForm->email;
            $user->save();
            return $this->redirectResponse("?controller=login");
        } else {
            return $this->templateResponse("fossil:signup");
        }
    }
    
    protected function createUser() {
        return UserModel::create($this->container);
    }
}
