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

namespace Fossil\Models;

use Fossil\OM,
    Doctrine\ORM\Query,
    Doctrine\Common\Collections\Collection,
    Doctrine\Common\Util\Debug;

/**
 * Description of PaginationProxy
 *
 * @author predakanga
 */
class PaginationProxy {
    protected $query;
    protected $collection;
    protected $pageSize;
    
    public function __construct($queryOrCollection, $pageSize = 10) {
        if($queryOrCollection instanceof Collection) {
            $this->collection = $queryOrCollection;
        } else {
            $this->query = $queryOrCollection;
        }
        $this->pageSize = $pageSize;
    }
    
    public function getItemCount() {
        if($this->collection) {
            return $this->collection->count();
        } else {
            /* @var $countQuery Query */
            $countQuery = clone $this->query;

            // BUGFIX: Clone doesn't copy parameters
            $countQuery->setParameters($this->query->getParameters());
            
            $countQuery->setHint(Query::HINT_CUSTOM_TREE_WALKERS, array('Fossil\DoctrineExtensions\CountSqlWalker'));
            $countQuery->setFirstResult(null)->setMaxResults(null);

            return $countQuery->getSingleScalarResult();
        }
    }
    
    public function getPage($page = 1) {
        $page = $page-1;
        if($this->collection) {
            return $this->collection->slice($page * $this->pageSize, $this->pageSize);
        } else {
            $this->query->setFirstResult($page * $this->pageSize);
            $this->query->setMaxResults($this->pageSize);
            return $this->query->getResult();
        }
    }
    
    public function getPageCount() {
        return ceil(((float)$this->getItemCount())/$this->pageSize);
    }
}

?>
