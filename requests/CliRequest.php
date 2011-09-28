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
 * @category Fossil Core
 * @package Fossil
 * @subpackage Requests
 * @license https://github.com/predakanga/Fossil/blob/master/LICENSE.txt New BSD License
 */

namespace Fossil\Requests;

use Symfony\Component\Console\Application,
    Symfony\Component\Console\Helper\HelperSet,
    Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper,
    Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper,
    Fossil\OM;

/**
 * Description of CliRequest
 *
 * @author predakanga
 */
class CliRequest extends BaseRequest {
    /**
     * @var Symfony\Component\Console\Application
     */
    protected $app;
    
    public function __construct() {
        // Figure out the app name
        $this->app = new Application($this->decideName(), "1.0");
        $helperSet = new HelperSet(array('db' => new ConnectionHelper(OM::ORM()->getEM()->getConnection()),
                                         'em' => new EntityManagerHelper(OM::ORM()->getEM())));
        $this->app->setHelperSet($helperSet);
        $this->app->setAutoExit(false);
        // And add any commands
        $this->registerCommands();
    }
    
    protected function decideName() {
        $appName = "Fossil";
        if(OM::overlayNamespace()) {
            $appName = basename(OM::overlayNamespace());
        } elseif(OM::appNamespace()) {
            $appName = basename(OM::appNamespace());
        }
        return $appName . " CLI";
    }
    
    protected function registerCommands() {
        // Add run DQL, for our own purposes
        $this->app->add(new \Doctrine\ORM\Tools\Console\Command\RunDqlCommand());
        foreach(array_keys(OM::getAllInstanced("Commands")) as $commandName) {
            $this->app->add(OM::obj("Commands", $commandName)->create());
        }
    }
    
    public function run() {
        $this->app->run();
    }
}

?>
