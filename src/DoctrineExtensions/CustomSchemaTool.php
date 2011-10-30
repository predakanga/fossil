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

namespace Fossil\DoctrineExtensions;

use Doctrine\DBAL\Schema\Comparator,
    Doctrine\DBAL\Schema\Schema,
    Doctrine\ORM\Tools\SchemaTool,
    Doctrine\ORM\EntityManager,
    Fossil\OM;

class CustomComparator extends Comparator {
    protected $retainDeleted;
    public $newModels = array();
    
    public function compare(Schema $fromSchema, Schema $toSchema) {
        $diff = parent::compare($fromSchema, $toSchema);
        
        $classes = OM::ORM()->getEM()->getMetadataFactory()->getAllMetadata();
        foreach($diff->newTables as $newTable) {
            $tableName = $newTable->getName();
            foreach($classes as $class) {
                if($class->getTableName() == $tableName)
                    $this->newModels[] = $class->getReflectionClass()->name;
            }
        }
        $this->newModels = array_unique($this->newModels);
        
        return $diff;
    }

}

/**
 * Description of CustomSchemaTool
 *
 * @author predakanga
 */
class CustomSchemaTool extends SchemaTool {
    protected $__em;
    protected $__platform;
    protected $__retainDeleted;
    public $newModels;
    
    public function __construct(EntityManager $em, $retainDeleted = true) {
        $this->__em = $em;
        $this->__platform = $em->getConnection()->getDatabasePlatform();
        $this->__retainDeleted = $retainDeleted;
        
        parent::__construct($em);
    }
    
    public function getCreateSchemaSql(array $classes)
    {
        $schema = $this->getSchemaFromMetadata($classes);
        
        $newModels = array();
        foreach($classes as $class)
            $newModels[] = $class->getReflectionClass()->name;
        $this->newModels = $newModels;
        
        return $schema->toSql($this->__platform);
    }
    
    public function getUpdateSchemaSql(array $classes, $saveMode=false)
    {
        $sm = $this->__em->getConnection()->getSchemaManager();

        $fromSchema = $sm->createSchema();
        $toSchema = $this->getSchemaFromMetadata($classes);

        $comparator = new CustomComparator($this->__retainDeleted);
        $schemaDiff = $comparator->compare($fromSchema, $toSchema);
        $this->newModels = $comparator->newModels;

        if ($saveMode) {
            return $schemaDiff->toSaveSql($this->__platform);
        } else {
            return $schemaDiff->toSql($this->__platform);
        }
    }
}


?>
