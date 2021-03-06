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

/**
 * Description of FirePHPSqlLogger
 *
 * @author predakanga
 */
class FirePHPSqlLogger implements \Doctrine\DBAL\Logging\SQLLogger {
    /**
     * @var FireFossil
     */
    protected $firephp;
    protected $queries = array();
    protected $queryNo = -1;
    
    public function __construct($firephp, $oldLogger) {
        $this->firephp = $firephp;
        /*
        foreach($oldLogger->getQueries() as $query) {
            unset($query['params']);
            $this->queries[] = $query;
        }*/
    }
    
    /** {@inheritDoc} */
    public function startQuery($sql, array $params = null, array $types = null) {
        $this->queryNo++;
        $this->queries[$this->queryNo] = array('sql' => $sql, 'params' => $params,
                                               'time' => microtime(true));
    }
    
    public function clearQueries() {
        $this->queryNo = -1;
        $this->queries = array();
    }
    
    /** {@inheritDoc} */
    public function stopQuery() {
        $startTime = $this->queries[$this->queryNo]['time'];
        $this->queries[$this->queryNo]['time'] = microtime(true) - $startTime;
    }
    
    public function getQuery() {
        return end($this->queries);
    }
    
    public function printTable() {
        $table = array();
        $table[] = array('SQL', 'Parameters', 'Time taken');
        foreach($this->queries as $query) {
            $table[] = $query;
        }
        
        $this->firephp->table("Fossil Queries (" . (count($table)-1) . " executed)", $table);
    }
}

?>
