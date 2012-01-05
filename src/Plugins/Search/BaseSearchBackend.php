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

namespace Fossil\Plugins\Search;

use Fossil\BaseDriver;

/**
 * Description of BaseSearchBackend
 *
 * @author predakanga
 * @F:Provides("Search")
 */
abstract class BaseSearchBackend extends BaseDriver {
    public function __construct($container) {
        $this->driverType = "Search";
        parent::__construct($container);
    }
    
    abstract public function clearIndexes($indexes);
    abstract public function indexEntity(ISearchable $entity);
    abstract public function removeEntity(ISearchable $entity);
    abstract public function findEntity(ISearchable $entity);
    public function updateEntity(ISearchable $entity) {
        $entityTuple = $this->findEntity($entity);
        if($entityTuple) {
            $this->removeEntity($entity);
        }
        $this->indexEntity($entity);
    }
    
    abstract public function optimizeIndex($indexName);
    abstract public function flush();
    abstract public function getSchemas($entities);
    
    abstract public function search($model, $query, $returnRaw = false, $boosts = array());
    /**
     * Searches an index, returns a PaginationProxy of the results
     * 
     * @param string $indexName Name of the index to search
     * @param string $query Query string
     * @param string $model Class name of the model to return
     * @param int $pageSize Page size for the pagination proxy
     * @return PaginationProxy Pagination proxy of the results
     * @internal Drivers can override this to make it more efficient
     */
    public function paginatedSearch($model, $query, $pageSize = 10) {
        $res = $this->search($model, $query);
        $ids = array();
        foreach($res as $r) {
            $ids[] = $r->dbId;
        }
        // Construct a query
        $builder = OM::ORM()->getEM()->createQueryBuilder();
        $builder = $builder->select("item")->from($model, "item")
                           ->where("item.id IN " . $builder->createPositionalParameter($ids));
        return new PaginationProxy($builder->getQuery(), $pageSize);
    }
    
    // Utility functions for use while indexing entities
    protected function getDataFromModel($entity, $accessor) {
        if(is_callable($accessor)) {
            return $accessor($entity);
        } elseif(strpos($accessor, "->") === FALSE) {
            return $entity->{$accessor};
        } else {
            // Decompose the accessors with explode
            $curModel = $entity;
            $accessParts = explode("->", $accessor);
            $i = 0;
            while($curPart = array_shift($accessParts)) {
                $curModel = $curModel->{$curPart};
            }
            return $curModel;
        }
    }
}
