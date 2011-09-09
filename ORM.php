<?php

namespace Fossil;

use Doctrine\DBAL\Schema\Comparator,
        Doctrine\DBAL\Schema\Schema,
        Doctrine\ORM\Tools\SchemaTool,
        Doctrine\ORM\EntityManager,
        Doctrine\ORM\Mapping\Driver\AnnotationDriver,
        Doctrine\Common\Annotations\AnnotationReader,
        Doctrine\Common\Annotations\AnnotationRegistry;

class CustomComparator extends Comparator {
    public $newModels = array();
    
    public function compare(Schema $fromSchema, Schema $toSchema) {
        $diff = parent::compare($fromSchema, $toSchema);
        
        $diff->removedTables = array();
        $diff->orphanedForeignKeys = array();
        
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

class CustomSchemaTool extends SchemaTool {
    protected $__em;
    protected $__platform;
    public $newModels;
    
    public function __construct(EntityManager $em) {
        $this->__em = $em;
        $this->__platform = $em->getConnection()->getDatabasePlatform();
        
        parent::__construct($em);
    }
    
    public function getUpdateSchemaSql(array $classes, $saveMode=false)
    {
        $sm = $this->__em->getConnection()->getSchemaManager();

        $fromSchema = $sm->createSchema();
        $toSchema = $this->getSchemaFromMetadata($classes);

        $comparator = new CustomComparator();
        $schemaDiff = $comparator->compare($fromSchema, $toSchema);
        $this->newModels = $comparator->newModels;

        if ($saveMode) {
            return $schemaDiff->toSaveSql($this->__platform);
        } else {
            return $schemaDiff->toSql($this->__platform);
        }
    }
}

class CustomAnnotationDriver extends AnnotationDriver {
    public function addPaths(array $paths)
    {
        $this->_paths = array_unique(array_merge($this->_paths, $paths));
        $this->_classNames = null;
    }
    
    /**
     * Factory method for the Annotation Driver
     *
     * @param array|string $paths
     * @param AnnotationReader $reader
     * @return AnnotationDriver
     * 
     * @note Have to include this method to, due to no late-static-binding
     */
    static public function create($paths = array(), AnnotationReader $reader = null)
    {
        if ($reader == null) {
            $reader = new AnnotationReader();
            $reader->setDefaultAnnotationNamespace('Doctrine\ORM\Mapping\\');
        }
        return new self($reader, $paths);
    }
}

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
        $config->setProxyDir(__DIR__.'/proxies');
        $config->setProxyNamespace('Fossil\Proxies');
        $config->setAutoGenerateProxyClasses(($appEnv == "development"));

        // Mapping Configuration (4)
        //$driverImpl = new Doctrine\ORM\Mapping\Driver\XmlDriver(__DIR__."/config/mappings/xml");
        //$driverImpl = new Doctrine\ORM\Mapping\Driver\XmlDriver(__DIR__."/config/mappings/yml");
        
        // Register the Doctrine annotations ourselves, as it's usually done by $config->newDefaultAnnotationDriver()
        AnnotationRegistry::registerFile('Doctrine/ORM/Mapping/Driver/DoctrineAnnotations.php');

        $this->driver = CustomAnnotationDriver::create(OM::FS()->fossilRoot() . D_S . "models");
        
        if(OM::FS()->overlayRoot())
            $this->driver->addPaths((array)(OM::FS()->overlayRoot() . D_S . "models"));
        
        $config->setMetadataDriverImpl($this->driver);

        // Caching Configuration (5)
        if ($appEnv == "development") {
            $cache = new \Doctrine\Common\Cache\ArrayCache();
        } else {
            $cache = new \Doctrine\Common\Cache\ApcCache();
        }
        $config->setMetadataCacheImpl($cache);
        $config->setQueryCacheImpl($cache);

        $this->config = $config;
        
        $this->evm = new \Doctrine\Common\EventManager();
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
    
    public function ensureSchema($retainDeleted = true) {
        if($retainDeleted) {
            $schemaTool = new CustomSchemaTool($this->getEM());
        } else
            $schemaTool = new SchemaTool($this->getEM());
        
        try
        {
            $schemaTool->updateSchema($this->getEM()->getMetadataFactory()->getAllMetadata());
            $this->ensureInitialDatasets($schemaTool->newModels);
        }
        // TODO: Need to make this more specific, to ignore only on SQLite
        catch(\Exception $e) {
            throw $e; 
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
