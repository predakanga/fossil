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

use Fossil\Object,
    Fossil\DoctrineExtensions\CustomSchemaTool,
    Fossil\DoctrineExtensions\CustomAnnotationDriver,
    Fossil\DoctrineExtensions\ReverseMappingGenerator,
    Fossil\DoctrineExtensions\DiscriminatorMapGenerator,
    Fossil\DoctrineExtensions\ActiveEntity\ActiveEntityManager,
    Fossil\DoctrineExtensions\QueryLogger,
    Fossil\DoctrineExtensions\FossilCache,
    Doctrine\DBAL\Types\Type,
    Doctrine\ORM\Tools\SchemaTool,
    Doctrine\ORM\EntityManager,
    Doctrine\Common\Annotations\AnnotationRegistry,
    Doctrine\Common\Util\Debug;

/**
 * Description of ORM
 *
 * @author predakanga
 * @F:Provides("ORM")
 * @F:DefaultProvider()
 */
class ORM extends Object {
    protected $em;
    protected $evm;
    protected $config;
    protected $driver;
    protected $logger;
    protected $mappingModifiers = array();
    /**
     * @F:Inject("Filesystem")
     * @var Fossil\Filesystem
     */
    protected $fs;
    /**
     * @F:Inject("AnnotationManager")
     * @var Fossil\Annotations\AnnotationManager
     */
    protected $annotationMgr;
    /**
     * @F:Inject("Database")
     * @var Fossil\Databases\BaseDatabase
     */
    protected $db;
    
    public function __construct($container) {
        parent::__construct($container);
        
        $appEnv = "development";

        // Load up custom types
        $types = array();
        foreach(glob($this->fs->fossilRoot() . D_S . "DoctrineExtensions" . D_S . "Types" . D_S . "*.php") as $type) {
            require_once($type);
        }
        
        // Grab the list of custom types
        foreach(get_declared_classes() as $class) {
            if(strpos($class, 'DoctrineExtensions\Types\\')) {
                $types += call_user_func(array($class, 'getRegisteredTypes'));
            }
        }
        
        // Use basic default EM for now
        $config = new \Doctrine\ORM\Configuration(); // (2)

        // Proxy Configuration (3)
        $tempDir = $this->fs->tempDir() . D_S . "proxies";
        // TODO: Only run this when the cache isn't primed
        if(!file_exists($tempDir))
            mkdir($tempDir);
        
        $config->setProxyDir($tempDir);
        $config->setProxyNamespace('Fossil\\Proxies');
        Autoloader::addNamespacePath("Fossil\\Proxies", $tempDir);
        $config->setAutoGenerateProxyClasses(($appEnv == "development"));

        // Register the Doctrine annotations ourselves, as it's usually done by $config->newDefaultAnnotationDriver()
        AnnotationRegistry::registerFile('Doctrine/ORM/Mapping/Driver/DoctrineAnnotations.php');

        $backingCache = FossilCache::create($this->container);
        $this->driver = CustomAnnotationDriver::create($backingCache);
        
        foreach($this->fs->roots(false) as $root) {
            if(is_dir($root . D_S . "Models"))
                $this->driver->addPaths((array)($root . D_S . "Models"));
        }
        
        $config->setMetadataDriverImpl($this->driver);

        // Caching Configuration (5)
        $config->setMetadataCacheImpl($backingCache);
        $config->setQueryCacheImpl($backingCache);
        $config->setClassMetadataFactoryName("\\Fossil\\DoctrineExtensions\\ActiveClassMetadataFactory");
        $this->logger = new QueryLogger();
        $config->setSQLLogger($this->logger);
        
        $this->config = $config;
        
        $this->evm = new \Doctrine\Common\EventManager();
        $this->mappingModifiers[] = $this->_new("MetadataListener", "ReverseMappingGenerator");
        $this->mappingModifiers[] = $this->_new("MetadataListener", "DiscriminatorMapGenerator");
        foreach($this->mappingModifiers as $gen)
            $this->evm->addEventListener(\Doctrine\ORM\Events::loadClassMetadata, $gen);
        
        if(!$this->db->getConnectionConfig()) {
            $conn = array('pdo' => $this->db->getPDO(), 'dbname' => null);
        } else {
            $conn = $this->db->getConnectionConfig();
        }
        
        $this->em = EntityManager::create($conn, $this->config, $this->evm);
        $this->em->getMetadataFactory()->setDIContainer($this->container);
        
        $realConn = $this->em->getConnection();
        $platform = $realConn->getDatabasePlatform();
        foreach($types as $typeName => $typeClass) {
            if(!Type::hasType($typeName))
                Type::addType($typeName, $typeClass);
            // Convention - our Doctrine type names must map directly to DB type names
            $platform->registerDoctrineTypeMapping($typeName, $typeName);
        }
    }
    
    public function registerPluginPaths() {
        $pluginsWithModels = array_filter($this->fs->pluginRoots(), function($root) {
            return is_dir($root . D_S . "Models");
        });
        $this->driver->addPaths(array_map(function($root) {
            return $root . D_S . "Models";    
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
    
    /**
     * @return \Fossil\DoctrineExtensions\QueryLogger
     */
    public function getLogger() {
        return $this->logger;
    }
    
    public function flush() {
        if($this->em)
            $this->em->flush();
    }
    
    public function ensureClassMetadata() {
        $this->getEM()->getMetadataFactory()->getAllMetadata();
    }
    
    public function ensureSchema($coreOnly = false, $retainDeleted = true) {
        $schemaTool = new CustomSchemaTool($this->getEM(), $retainDeleted);
        
        if(!$coreOnly) {
            // For all already loaded metadata, run them through the reverse mapping generator again
            foreach($this->getEM()->getMetadataFactory()->getLoadedMetadata() as $md) {
                $args = new \Doctrine\ORM\Event\LoadClassMetadataEventArgs($md, $this->em);
                foreach($this->mappingModifiers as $gen)
                    $gen->loadClassMetadata($args);
            }
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
            $schemaTool->updateSchema($allMD, true);
            $this->ensureInitialDatasets($schemaTool->newModels);
        }
        // TODO: Need to make this more specific, to ignore only on SQLite
        catch(\Doctrine\DBAL\DBALException $e) {
            // If it's SQLite, try just creating it instead
            try {
                $schemaTool->createSchema($allMD);
                $this->ensureInitialDatasets($schemaTool->newModels);
            }
            catch(\Exception $e) {
                // If we hit here, it most likely means that we're set up already
            }
        }
    }
    
    protected function ensureDataset($model, $modelList) {
        $modelList = $this->ensureDependantDatasets($model, $modelList);
        $dataset = $modelList[$model];
        
        foreach($dataset as $data) {
            $instance = $model::createFromArray($this->container, $data);
            $instance->save();
        }
        // Flush after each model, so that subsequent models can use the new entities
        self::flush();
        
        unset($modelList[$model]);
        return $modelList;
    }
    
    protected function ensureDependantDatasets($model, $modelList) {
        $md = $this->getEM()->getClassMetadata($model);
        foreach($md->getAssociationMappings() as $mapping) {
            // Skip the owned side
            if(isset($mapping['mappedBy'])) {
                continue;
            }
            $targetModel = $md->getAssociationTargetClass($mapping['fieldName']);
            if(isset($modelList[$targetModel]) && $targetModel != $model) {
                $modelList = $this->ensureDataset($targetModel, $modelList);
            }
        }
        
        return $modelList;
    }
    
    protected function ensureInitialDatasets($newModels) {
        $modelData = array();
        foreach($newModels as $model) {
            $annos = $this->annotationMgr->getClassAnnotations($model, "F:InitialDataset", false);
            if($annos) {
                $modelData[$model] = array();
                foreach($annos as $anno) {
                    $modelData[$model] = array_merge($modelData[$model], $anno->getData($this->container));
                }
            }
        }
        
        // Naive approach to autofilling data in order
        foreach(array_keys($modelData) as $name) {
            // If we've already added this data, skip
            if(!isset($modelData[$name]))
                continue;
            
            $modelData = $this->ensureDataset($name, $modelData);
        }
    }
}

?>
