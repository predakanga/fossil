<?php

namespace Fossil\Databases;

use Fossil\OM;

/**
 * Description of PgSQLDB
 *
 * @author predakanga
 * @F:Object(type="Database", name="PostgreSQL")
 */
class PgSQLDB extends BaseDatabase {
    public static function getName() { return "PgSQL"; }
    public static function getVersion() { return 1.0; }
    public static function usable() { return extension_loaded('pdo') && in_array("pgsql", \PDO::getAvailableDrivers()); }
    public static function getForm() { return OM::Form("PgSQLConfig"); }
    
    public function __construct($config = null) {
        // If we don't have params, throw an error
        // TODO: Make this a proper exception
        if(!$config)
            $config = $this->getDefaultConfig();
        
        parent::__construct($config);
    }
    
    public function getPDO() {
        if(!$this->pdo) {
            $dsn = "pgsql:host={$this->config['host']};dbname={$this->config['db']};port={$this->config['port']}";
            $this->pdo = new \PDO($dsn, $this->config['user'], $this->config['pass']);
        }
        return $this->pdo;
    }
}

?>
