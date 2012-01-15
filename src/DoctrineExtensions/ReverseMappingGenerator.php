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

use Doctrine\ORM\Event\LoadClassMetadataEventArgs,
    Doctrine\ORM\Mapping\ClassMetadata,
    Doctrine\ORM\Mapping\ClassMetadataInfo,
    Doctrine\Common\Util\Debug;

/**
 * Description of ReverseMappingGenerator
 *
 * @author predakanga
 */
class ReverseMappingGenerator extends BaseMetadataListener {
    protected $outOfBand = array();
    /**
     * @F:Inject("AnnotationManager")
     * @var Fossil\Annotations\AnnotationManager
     */
    protected $annotationMgr;
    
    public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs) {
        $classMetadata = $eventArgs->getClassMetadata();
        $em = $eventArgs->getEntityManager();
        // Enumerate the mappings to add to this class
        $classes = $this->annotationMgr->getClassesWithPropertyAnnotation("F:GenerateReverse");
        foreach($classes as $class) {
            // Don't check our own class for annotations
            if($classMetadata->getName() == $class) {
                continue;
            }
            $reflClass = new \ReflectionClass($class);
            foreach($reflClass->getProperties() as $property) {
                // Skip over inherited properties
                if($property->class != $class) {
                    continue;
                }
                $annos = $this->annotationMgr->getPropertyAnnotations($property, "F:GenerateReverse");
                if(count($annos)) {
                    // Identified a reverse association
                    $md = null;
                    // Multiple codepaths... if we have already have loaded metadata, use it
                    if($em->getMetadataFactory()->hasMetadataFor($class)) {
                        // Use the existing metadata
                        // Identified a reverse association, now try to get the metadata
                        $md = $eventArgs->getEntityManager()->getClassMetadata($class);
                    } else {
                        // Load the metadata out-of-band, otherwise
                        if(!isset($this->outOfBand[$class])) {
                            $cm = new ActiveClassMetadata($class, $this->container, null);
                            $config = $em->getConfiguration();
                            $config->getMetadataDriverImpl()->loadMetadataForClass($class, $cm);
                            $this->outOfBand[$class] = $cm;
                        }
                        $md = $this->outOfBand[$class];
                    }
                    
                    if($md->hasAssociation($property->name) &&
                       $md->getAssociationTargetClass($property->name) == $classMetadata->getName()) {
                        // Generate the reverse and add it in
                        $mapping = $md->getAssociationMapping($property->name);
                        if((!$mapping['isOwningSide']) && ($mapping['type'] != ClassMetadataInfo::ONE_TO_MANY)) {
                            throw new \Exception("@F:GenerateReverse may only be used on the owning side of associations");
                        }
                        $reverseMapping = $this->invertMapping($mapping);
                        // Skip out early if we've already mapped this association
                        if($classMetadata->hasAssociation($reverseMapping['fieldName'])) {
                            continue;
                        }
                        switch($reverseMapping['type']) {
                            case ClassMetadataInfo::MANY_TO_MANY:
                                $classMetadata->mapManyToMany($reverseMapping);
                                break;
                            case ClassMetadataInfo::ONE_TO_MANY:
                                $classMetadata->mapOneToMany($reverseMapping);
                                break;
                            case ClassMetaDataInfo::MANY_TO_ONE:
                                $classMetadata->mapManyToOne($reverseMapping);
                                break;
                            case ClassMetadataInfo::ONE_TO_ONE:
                                $classMetadata->mapOneToOne($reverseMapping);
                                break;
                        }
                    }
                }
            }
        }
    }
    
    protected function invertMapping($mapping) {
        $newMapping = array('declared' => $mapping['targetEntity']);
        $newMapping['targetEntity'] = $mapping['sourceEntity'];
        
        switch($mapping['type']) {
            case ClassMetadataInfo::MANY_TO_MANY:
                $newMapping['type'] = ClassMetadataInfo::MANY_TO_MANY;
                break;
            case ClassMetadataInfo::MANY_TO_ONE:
                $newMapping['type'] = ClassMetadataInfo::ONE_TO_MANY;
                break;
            case ClassMetadataInfo::ONE_TO_ONE:
                $newMapping['type'] = ClassMetadataInfo::ONE_TO_ONE;
                break;
            case ClassMetadataInfo::ONE_TO_MANY:
                $newMapping['type'] = ClassMetaDataInfo::MANY_TO_ONE;
        }
        // Required: targetEntity, fieldName
        // Use mappedBy
        if($newMapping['type'] == ClassMetadataInfo::MANY_TO_ONE) {
            if(!isset($mapping['mappedBy']))
                throw new \Exception("@F:GenerateReverse on an N-1 association must be used with an association with mappedBy specified");
            $newMapping['fieldName'] = $mapping['mappedBy'];
            $newMapping['inversedBy'] = $mapping['fieldName'];
        } else {
            if(!isset($mapping['inversedBy']))
                throw new \Exception("@F:GenerateReverse must be used with an association with inversedBy specified");
            $newMapping['fieldName'] = $mapping['inversedBy'];
            $newMapping['mappedBy'] = $mapping['fieldName'];
        }
        
        return $newMapping;
    }
}
