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

namespace Fossil\Plugins\Search\Tasks;

use Fossil\OM,
    Fossil\Tasks\StreamingTask,
    Symfony\Component\Console\Output\OutputInterface;

/**
 * Description of InitSearchIndex
 *
 * @author predakanga
 */
class InitSearchIndexes extends StreamingTask {
    protected $curOffset;
    protected $curQuery;
    protected $batchSize = 250;
    protected $cullPoint = 1500;
    /**
     * @F:Inject("Search")
     * @var BaseSearchBackend
     */
    protected $search;
    /**
     * @F:Inject("ORM")
     * @var Fossil\ORM
     */
    protected $orm;
    
    public function runOneIteration(OutputInterface $out) {
        // Query for the objects
        $this->curQuery->setFirstResult($this->curOffset);
        $results = $this->curQuery->getResult();
        foreach($results as $result) {
            $this->search->indexEntity($result);
        }
    }

    public function run(OutputInterface $out) {
        // Memory intensive process, so up the limit
        ini_set('memory_limit', '256M');
        // Check what models implement ISearchable
        $toSearch = array();
        foreach($this->orm->getEM()->getMetadataFactory()->getAllMetadata() as $md) {
            $class = $md->getName();
            $reflClass = $md->getReflectionClass();
            if($reflClass->implementsInterface('Fossil\Plugins\Search\ISearchable')) {
                $toSearch[] = $class;
            }
        }
        // Then get the counts of each
        $searchData = array();
        foreach($toSearch as $class) {
            $q = $this->orm->getEM()->createQuery("SELECT COUNT(u) FROM " . $class . " u");
            $searchData[$class] = $q->getSingleScalarResult();
        }
        $out->writeln("Wiping indexes");
        $indexes = array_map(function($model) { return call_user_func(array($model, "getIndexName")); },
                             $toSearch);
        $this->search->clearIndexes($indexes);
        $out->writeln("Done");
        $out->writeln("Indexing:");
        // Then index items, 100 at a time
        foreach($searchData as $model => $entCount) {
            $out->writeln("\t$model ($entCount items)");
            $this->curOffset = 0;
            $this->curQuery = $this->orm->getEM()->createQuery("SELECT entity FROM $model entity");
            $this->curQuery->setMaxResults($this->batchSize);
            $curIdx = call_user_func(array($model, "getIndexName"));
            
            while($this->curOffset < $entCount) {
                $this->runOneIteration($out);
                $this->curOffset += $this->batchSize;
                // Cull the entities if we have too many
                if($this->orm->getEM()->getUnitOfWork()->size() > $this->cullPoint) {
                    $out->writeln("\t\tIndexed {$this->curOffset} items");
                    $this->search->flush();
                    $this->orm->getEM()->clear();
                    gc_collect_cycles();
                }
            }
            // Finally, optimize the index, so that the flushing doesn't slow things down
            $out->writeln("\t\tOptimizing index");
            $this->search->optimizeIndex($curIdx);
            $this->search->flush();
        }
        // And disable batch mode again
        $this->result = self::RESULT_SUCCEEDED;
    }
}
