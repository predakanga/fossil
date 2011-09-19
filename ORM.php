<?php

/**
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
 * 
 * @author predakanga
 * @since 0.1
 * @category Fossil Core
 * @package Fossil
 * @license https://github.com/predakanga/Fossil/blob/master/LICENSE.txt New BSD License
 */

namespace Fossil;

use Fossil\DoctrineExtensions\CustomSchemaTool,
    Fossil\DoctrineExtensions\CustomAnnotationDriver,
    Fossil\DoctrineExtensions\ReverseMappingGenerator,
    DoctrineExtensions\ActiveEntity\ActiveEntityManager,
    Doctrine\ORM\Tools\SchemaTool,
    Doctrine\ORM\EntityManager,
    Doctrine\Common\Annotations\AnnotationRegistry;

/**
 * Description of ORM
 *
 * @author predakanga
 * @F:Object("ORM")
 */
class ORM {
    protected $em;
    protected $evm;
    protected $config;
    protected $driver;
    
    public function __construct() {
        $appEnv = "development";
        
        // Use basic default EM for now
        $config = new \Doctrine\ORM\Configuration(); // (2)

        // Proxy Configuration (3)
        $config->setProxyDir(OM::FS()->tempDir() . D_S .'proxies');
        $config->setProxyNamespace('Fossil\\Proxies');
        Autoloader::addNamespacePath("Fossil\\Proxies", OM::FS()->tempDir() . D_S .'proxies');
        $config->setAutoGenerateProxyClasses(($appEnv == "development"));

        // Mapping Configuration (4)
        //$driverImpl = new Doctrine\ORM\Mapping\Driver\XmlDriver(__DIR__."/config/mappings/xml");
        //$driverImpl = new Doctrine\ORM\Mapping\Driver\XmlDriver(__DIR__."/config/mappings/yml");
        
        // Register the Doctrine annotations ourselves, as it's usually done by $config->newDefaultAnnotationDriver()
        AnnotationRegistry::registerFile('Doctrine/ORM/Mapping/Driver/DoctrineAnnotations.php');

        $this->driver = CustomAnnotationDriver::create(OM::FS()->fossilRoot() . D_S . "models");
        
        CustomAnnotationDriver::create(array());
        
        foreach(OM::FS()->roots(false) as $root) {
            if(is_dir($root . D_S . "models"))
                $this->driver->addPaths((array)($root . D_S . "models"));
        }
        
        $config->setMetadataDriverImpl($this->driver);

        // Caching Configuration (5)
        if ($appEnv == "development") {
            $cache = new \Doctrine\Common\Cache\ArrayCache();
        } else {
            $cache = new \Doctrine\Common\Cache\ApcCache();
        }
        $config->setMetadataCacheImpl($cache);
        $config->setQueryCacheImpl($cache);
        $config->setClassMetadataFactoryName("\\Fossil\\DoctrineExtensions\\ActiveClassMetadataFactory");

        $this->config = $config;
        
        $this->evm = new \Doctrine\Common\EventManager();
        $this->evm->addEventListener(\Doctrine\ORM\Events::loadClassMetadata, new ReverseMappingGenerator());
        if(!OM::Database()->getConnectionConfig())
            $conn = array('pdo' => OM::Database()->getPDO(), 'dbname' => null);
        else
            $conn = OM::Database()->getConnectionConfig();
        $this->em = EntityManager::create($conn, $this->config, $this->evm);
    }
    
    public function registerPaths() {
        $pluginsWithModels = array_filter(OM::FS()->pluginRoots(), function($root) {
            return is_dir($root . D_S . "models");
        });
        $this->driver->addPaths(array_map(function($root) {
            return $root . D_S . "models";    
        }, $pluginsWithModels));
    }
    
    /**
     * @return \Doctrine\ORM\EntityManager
     */
    public function getEM() {
        return $this->em;
    }
    
    /**
     * @return \Doctrine\Common\EventManager
     */
    public function getEVM() {
        return $this->evm;
    }
    
    public function flush() {
        if($this->em)
            $this->em->flush();
    }
    
    public function ensureSchema($coreOnly = false, $retainDeleted = true) {
        $schemaTool = new CustomSchemaTool($this->getEM(), $retainDeleted);
        
        if(!$coreOnly) {
            $allMD = $this->getEM()->getMetadataFactory()->getAllMetadata();
        } else {
            $allMD = array();
            foreach($this->getEM()->getMetadataFactory()->getAllMetadata() as $md) {
                if($md->getReflectionClass()->getNamespaceName() == "Fossil\\Models")
                    $allMD[] = $md;
            }
        }
        
        try
        {
            $schemaTool->updateSchema($allMD);
            $this->ensureInitialDatasets($schemaTool->newModels);
        }
        // TODO: Need to make this more specific, to ignore only on SQLite
        catch(\Doctrine\DBAL\DBALException $e) {
            // If it's SQLite, try just creating it instead
            $schemaTool->createSchema($allMD);
            $this->ensureInitialDatasets($schemaTool->newModels);
        }
    }
    
    protected function ensureDataset($model, $dataset) {
        foreach($dataset as $data) {
            $instance = $model::createFromArray($data);
            $instance->save();
        }
        // Flush after each model, so that subsequent models can use the new entities
        self::flush();
    }
    
    protected function ensureInitialDatasets($newModels) {
        $modelData = array();
        foreach($newModels as $model) {
            $annos = OM::Annotations()->getClassAnnotations($model, "F:InitialDataset");
            if($annos) {
                $modelData[$model] = array();
                foreach($annos as $anno) {
                    $modelData[$model] = array_merge($modelData[$model], $anno->getData());
                }
            }
        }
        
        // Naive approach to autofilling data in order
        foreach(array_keys($modelData) as $name) {
            // If we've already added this data, skip
            if(!isset($modelData[$name]))
                continue;
            // Otherwise, check for associations
            $metadata = $this->getEM()->getClassMetadata($name);
            foreach($metadata->getAssociationMappings() as $mapping) {
                // Skip the owned side
                if(isset($mapping['mappedBy']))
                    continue;
                $targetName = $metadata->getAssociationTargetClass($mapping['fieldName']);
                if(isset($modelData[$targetName]) && $targetName != $name) {
                    $this->ensureDataset($targetName, $modelData[$targetName]);
                    unset($modelData[$targetName]);
                }
            }
            // Finally, add the data
            $this->ensureDataset($name, $modelData[$name]);
            unset($modelData[$name]);
        }
    }
}

?>
