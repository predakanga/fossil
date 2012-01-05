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

namespace Fossil\Commands;

use Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface,
    Symfony\Component\Console\Input\InputDefinition,
    Symfony\Component\Console\Input\InputArgument,
    Fossil\OM;

/**
 * Description of RunRequest
 *
 * @author predakanga
 */
class RunRequest extends BaseCommand {
    protected function configure() {
        $this->setName("runRequest");
        $this->setDescription("Runs a request on a controller, outputs the resulting variables");
        $definition = new InputDefinition(array(new InputArgument("controller", InputArgument::OPTIONAL),
                                                new InputArgument("action", InputArgument::OPTIONAL),
                                                new InputArgument("argument", InputArgument::IS_ARRAY | InputArgument::OPTIONAL)));
        $this->setDefinition($definition);
    }
    
    protected function execute(InputInterface $input, OutputInterface $output) {
        $start_time = microtime(true);
        
        $controller = $input->getArgument("controller");
        $action = $input->getArgument("action");
        $args_raw = $input->getArgument("argument");
        $args = array();
        for($i = 0; $i < count($args_raw); $i++) {
            $args[$args_raw[$i]] = $args_raw[$i+1];
        }
        
        $req = OM::obj("Requests", "InternalRequest")->create($controller, $action, $args);
        $response = $req->run();
        
        $end_time = microtime(true);
        
        $time_passed = ($end_time - $start_time);
        
        $output->writeln("Ran the request. Took " . $time_passed . " seconds");
        \Doctrine\Common\Util\Debug::dump($response, 3);
    }
}
