<?php

namespace Fossil\Databases;

use Fossil\OM;

/**
 * Description of MySQLDB
 *
 * @author predakanga
 * @F:Object(type="Database", name="MySQL")
 */
class MySQLDB extends BaseDatabase {
    public static function getName() { return "MySQL"; }
    public static function getVersion() { return 1.0; }
    public static function usable() { return extension_loaded('pdo') && in_array("mysql", \PDO::getAvailableDrivers()); }
    public static function getForm() { return OM::Form("MySQLConfig"); }
    
    protected $pdo;
    
    public function __construct($config = null) {
        // If we don't have params, throw an error
        // TODO: Make this a proper exception
        if(!$config)
            $config = $this->getDefaultConfig();
        
        parent::__construct($config);
    }
    
    public function getPDO() {
        if(!$this->pdo) {
            $dsn = "mysql:host={$this->config['host']};dbname={$this->config['db']};port={$this->config['port']}";
            $this->pdo = new \PDO($dsn, $this->config['user'], $this->config['pass']);
        }
        return $this->pdo;
    }
}

?>
