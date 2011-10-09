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
    Doctrine\Common\Util\Debug,
    Fossil\OM;

/**
 * Description of DiscriminatorMapGenerator
 *
 * @author predakanga
 */
class DiscriminatorMapGenerator {
    public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs) {
        $classMetadata = $eventArgs->getClassMetadata();
        $ourClass = $classMetadata->getReflectionClass()->getName();
        $em = $eventArgs->getEntityManager();

        // Ensure that it uses a string discriminator column
        if($classMetadata->discriminatorColumn['type'] != "string")
            return;
        
        // And that it's the root entity
        if($classMetadata->rootEntityName != $classMetadata->name)
            return;

        $discriminatorMap = $classMetadata->discriminatorMap;

        if($classMetadata->isInheritanceTypeJoined() || $classMetadata->isInheritanceTypeSingleTable()) {
            // Grab the list of extension entities that are subclasses
            $allEntities = OM::Annotations()->getClassesWithAnnotation('F:ExtendsDiscriminatorMap');
            foreach($allEntities as $ent) {
                if(is_subclass_of($ent, $ourClass)) {
                    $discriminatorMap[$ent] = $ent;
                }
            }
        }
        $classMetadata->setDiscriminatorMap($discriminatorMap);
    }
}

?>
