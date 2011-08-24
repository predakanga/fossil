<?php

namespace Fossil;

/**
 * Description of ORM
 *
 * @author predakanga
 * @F:Object("ORM")
 */
class ORM {
    protected $em;
    protected $evm;
    
    public function __construct() {
        $appEnv = "development";
        
        // Use basic default EM for now
        $config = new Doctrine\ORM\Configuration(); // (2)

        // Proxy Configuration (3)
        $config->setProxyDir(__DIR__.'/proxies');
        $config->setProxyNamespace('Fossil\Proxies');
        $config->setAutoGenerateProxyClasses(($appEnv == "development"));

        // Mapping Configuration (4)
        //$driverImpl = new Doctrine\ORM\Mapping\Driver\XmlDriver(__DIR__."/config/mappings/xml");
        //$driverImpl = new Doctrine\ORM\Mapping\Driver\XmlDriver(__DIR__."/config/mappings/yml");
        $driverImpl = $config->newDefaultAnnotationDriver(__DIR__."/models");
        $config->setMetadataDriverImpl($driverImpl);

        // Caching Configuration (5)
        if ($appEnv == "development") {
            $cache = new \Doctrine\Common\Cache\ArrayCache();
        } else {
            $cache = new \Doctrine\Common\Cache\ApcCache();
        }
        $config->setMetadataCacheImpl($cache);
        $config->setQueryCacheImpl($cache);

        // database configuration parameters (6)
        $conn = array(
            'driver' => 'pdo_sqlite',
            'path' => 'db.sqlite',
        );

        // obtaining the entity manager (7)
        $this->evm = new Doctrine\Common\EventManager();
        $this->em = \Doctrine\ORM\EntityManager::create($conn, $config, $evm);
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
}

?>
