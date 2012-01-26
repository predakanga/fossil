<?php

/*
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
 */

namespace Fossil\Plugins\Firebug;

use Fossil\BaseInit,
    Fossil\Plugins\Users\Models\User;

/**
 * Description of Init
 *
 * @author predakanga
 */
class Init extends BaseInit {
    /**
     * @var FireFossil
     */
    protected $firephp;
    
    public function everyTimeInit() {
        // If we're not a dev, quit immediately
        $user = User::me($this->container, false);
        if($user && $user->isDev()) {
            $this->firephp = FireFossil::getInstance();
            // Only continue if we've detected the client extension
            if($this->firephp->detectClientExtension()) {
                $this->firephp->setIsActive(true);
                // Register our own error manager
                $this->container->registerType("ErrorManager", "Fossil\Plugins\Firebug\ErrorManager");
                // And our own debug logger
                $this->container->registerType("DebugLogger", "Fossil\Plugins\Firebug\Logging\Debug\FirebugDebugLogger", true, true);
                // Inject FirePHP into the error manager and logger
                $this->container->get("ErrorManager")->setFirephp($this->firephp);
                $this->container->get("DebugLogger")->setFirephp($this->firephp);
                $this->container->get("Dispatcher")->setFirephp($this->firephp);
                
                // Set the log level
                if(isset($_GET['FOSSIL_LOG']) && is_numeric($_GET['FOSSIL_LOG'])) {
                    $this->container->get("DebugLogger")->setLevel((int)$_GET['FOSSIL_LOG']);
                }

                // Set the SQL logger up
                $oldLogger = $this->container->get("ORM")->getLogger();
                $newLogger = new FirePHPSqlLogger($this->firephp, $oldLogger);
                $this->container->get("ORM")->setLogger($newLogger);
            }
        }
        
        parent::everyTimeInit();
    }
}

?>
