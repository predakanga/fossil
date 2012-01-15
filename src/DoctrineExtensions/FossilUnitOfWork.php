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

use Doctrine\ORM\UnitOfWork,
    Doctrine\ORM\EntityManager,
    Doctrine\ORM\Mapping\ClassMetadata;

/**
 * Description of FossilUnitOfWork
 *
 * @author predakanga
 */
class FossilUnitOfWork extends UnitOfWork {
    private $em;
    private $container;
    private $mdNameMap = array();
    private $persisters = array();
    
    public function __construct(EntityManager $em, $container) {
        $this->em = $em;
        $this->container = $container;
        
        parent::__construct($em);
    }

    public function computeChangeSet(ClassMetadata $class, $entity)
    {
        if($class instanceof ActiveClassMetadata) {
            $class = $this->em->getClassMetadata($class->origClassName);
        }
        return parent::computeChangeSet($class, $entity);
    }
    
    public function commit() {
        // Process each MD, converting all metadata to refer to the compiled class
        $allMD = $this->em->getMetadataFactory()->getLoadedMetadata();
        $modifiedMD = array();
        $this->mdNameMap = array();
        foreach($allMD as $md) {
            $finalName = $this->container->mapClass($md->name);
            if($finalName != $md->name) {
                $modifiedMD[$md->name] = $md;
                $this->mdNameMap[$finalName] = $md->name;
                $md->name = $finalName;
            }
        }
        // Then run the commit
        parent::commit();
        // And finally, return the MDs to normal
        foreach($modifiedMD as $origName => $md) {
            $md->name = $origName;
        }
    }
    
    public function getEntityPersister($entityName)
    {
        if ( ! isset($this->persisters[$entityName])) {
            $class = $this->em->getClassMetadata($entityName);
            if(isset($this->mdNameMap[$class->name])) {
                $class = clone $class;
                $class->name = $this->mdNameMap[$class->name];
            }
            if ($class->isInheritanceTypeNone()) {
                $persister = new \Doctrine\ORM\Persisters\BasicEntityPersister($this->em, $class);
            } else if ($class->isInheritanceTypeSingleTable()) {
                $persister = new \Doctrine\ORM\Persisters\SingleTablePersister($this->em, $class);
            } else if ($class->isInheritanceTypeJoined()) {
                $persister = new \Doctrine\ORM\Persisters\JoinedSubclassPersister($this->em, $class);
            } else {
                $persister = new \Doctrine\ORM\Persisters\UnionSubclassPersister($this->em, $class);
            }
            $this->persisters[$entityName] = $persister;
        }
        return $this->persisters[$entityName];
    }
}

?>
