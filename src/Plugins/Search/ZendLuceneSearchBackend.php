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

use Fossil\OM,
    Fossil\Models\PaginationProxy;

// On include, add our Zend library to our search path
set_include_path(get_include_path() . PATH_SEPARATOR . __DIR__ . D_S . "libs");

/**
 * Description of ZendLuceneSearch
 *
 * @author predakanga
 * @F:DefaultProvider
 */
class ZendLuceneSearchBackend extends BaseSearchBackend {
    protected $indexes = array();
    protected $batchMode = false;
    /**
     * @F:Inject("Filesystem")
     * @var Fossil\Filesystem
     */
    protected $fs;
    
    static function usable() {
        require_once "Zend/Search/Lucene.php";
        return class_exists("Zend_Search_Lucene");
    }
    static function getName() {
        return "Zend_Lucene search backend";
    }
    static function getVersion() {
        return "1.6";
    }
    static function getFormName() {
        return null;
    }
    protected function getDefaultConfig() {
        return array();
    }
    
    public function __construct($container) {
        parent::__construct($container);
        
        require_once "Zend/Search/Lucene.php";
        require_once "StandardAnalyzer/Analyzer/Standard/English.php";
        // Set the default analyzer to the stemming, english stop-words analyzer
        // http://codefury.net/projects/StandardAnalyzer/
        \Zend_Search_Lucene_Analysis_Analyzer::setDefault(new \StandardAnalyzer_Analyzer_Standard_English());
    }
    protected function getIndexDir($indexName) {
        $dir = $this->fs->tempDir() . D_S . "search" . D_S . "lucene" . D_S . $indexName;
        if(!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }
        return $dir;
    }
    public function loadIndex($indexName) {
        $idx = $this->getIndex($indexName);
        
        // Load dictionary index structures
        require_once "Zend/Search/Lucene/Index/Term.php";
        $idx->hasTerm(new \Zend_Search_Lucene_Index_Term('dummy_data', 'dummy_fieldname'));
    }
    /**
     *
     * @param string $indexName
     * @return Zend_Search_Lucene
     */
    protected function getIndex($indexName) {
        if(!isset($this->indexes[$indexName])) {
            $idxDir = $this->getIndexDir($indexName);
            try
            {
                $idx = \Zend_Search_Lucene::open($idxDir);
            }
            catch(\Zend_Search_Lucene_Exception $e) {
                $idx = \Zend_Search_Lucene::create($idxDir);
            }
            $this->indexes[$indexName] = $idx;
        }
        return $this->indexes[$indexName];
    }
    public function clearIndexes($indexes) {
        foreach($indexes as $indexName) {
            $dir = $this->getIndexDir($indexName);
            $files = glob($dir . D_S . "*");
            foreach($files as $file) {
                unlink($file);
            }
        }
    }
    public function optimizeIndex($indexName) {
        $this->getIndex($indexName)->optimize();
    }
    public function flush($clear = false) {
        foreach($this->indexes as $idxName => $idx) {
            $idx->commit();
            if($clear) {
                $this->indexes[$idxName] = null;
            }
        }
    }
    public function getSchemas($entities) {
        return array();
    }
    
    public function indexEntity(ISearchable $entity) {
        $idx = $this->getIndex(call_user_func(array($model, "getIndexName")));
        // Create a new document
        $entityDoc = new \Zend_Search_Lucene_Document();
        // Add the index field
        $idField = call_user_func(array($model, "getIDField"));
        $entityDoc->addField(\Zend_Search_Lucene_Field::keyword('dbId', $entity->$idField));
        // Then add each regular field
        $hasData = false;
        foreach(call_user_func(array($model, "getSearchFields")) as $field => $type) {
            $accessor = $field;
            if(isset($type['accessor'])) {
                $accessor = $type['accessor'];
            }
            $type = $type['options'];
            $value = $this->getDataFromModel($entity, $accessor);
            if(!empty($value)) {
                $hasData = true;
            }
            $fieldObj = null;
            if($type & ISearchable::SEARCH_FIELD_BINARY) {
                $fieldObj = \Zend_Search_Lucene_Field::binary($field, $value);
            } elseif(!($type & ISearchable::SEARCH_FIELD_STORED)) {
                $fieldObj = \Zend_Search_Lucene_Field::unStored($field['name'], $value);
            } elseif($type & ISearchable::SEARCH_FIELD_TOKENIZED) {
                $fieldObj = \Zend_Search_Lucene_Field::text($field['name'], $value);
            } elseif($type & ISearchable::SEARCH_FIELD_INDEXED) {
                $fieldObj = \Zend_Search_Lucene_Field::keyword($field['name'], $value);
            } else {
                $fieldObj = \Zend_Search_Lucene_Field::unIndexed($field['name'], $value);
            }
            $entityDoc->addField($fieldObj);
        }
        if($hasData) {
            $idx->addDocument($entityDoc);
        }
    }
    public function removeEntity(ISearchable $entity) {
        $idx = $this->getIndex(call_user_func(array($model, "getIndexName")));
        $entityDoc = $this->findEntity($entity);
        $idx->delete($entityDoc[0]);
    }
    public function findEntity(ISearchable $entity) {
        $idx = $this->getIndex(call_user_func(array($model, "getIndexName")));
        $idField = call_user_func(array($model, "getIDField"));
        $term = new \Zend_Search_Lucene_Index_Term('dbId', $entity->$idField);
        
        $docs = $idx->termDocs($term);
        
        if(count($docs) > 0) {
            return array($docs[0], $idx->getDocument($docs[0]));
        }
        return null;
    }
    
    public function search($model, $query, $returnRaw = false, $boosts = array()) {
        $indexName = call_user_func(array($model, "getIndexName"));
        $idx = $this->getIndex($indexName);
        $results = $idx->find($query);
        if($returnRaw) {
            return $results;
        }
        // Map the results to result objects
        $allResults = array();
        foreach($results as $result) {
            $data = array();
            $doc = $result->getDocument();
            foreach($doc->getFieldNames() as $key) {
                $data[$key] = $result->getFieldValue($key);
            }
            $allResults[] = new SearchResult($data);
        }
        return $allResults;
    }

    public function multiSearch($modelsWithOptions, $query, $returnRaw = false) {
        throw new \Exception("Unimplemented");
    }

    public function paginatedSearch($query, $model, $pageSize = 10) {
        $res = $this->search($model, $query, true);
        $ids = array();
        foreach($res as $r) {
            $ids[] = $r->dbId;
        }
        // Construct a query
        $builder = $this->orm->getEM()->createQueryBuilder();
        $builder = $builder->select("item")->from($model, "item")
                           ->where("item.id IN " . $builder->createPositionalParameter($ids));
        return new PaginationProxy($builder->getQuery(), $pageSize);
    }
}
