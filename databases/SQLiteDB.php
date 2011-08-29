<?php

namespace Fossil\Databases;

use Fossil\OM;

/**
 * Description of SQLiteDB
 *
 * @author predakanga
 * @F:Object(type="Database", name="SQLite")
 */
class SQLiteDB extends BaseDatabase {
    public static function getName() { return "SQLite"; }
    public static function getVersion() { return 1.0; }
    public static function usable() { return extension_loaded('pdo') && in_array("sqlite", \PDO::getAvailableDrivers()); }
    public static function getForm() { return OM::Form("SQLiteConfig"); }
    
    public function __construct($config = null) {
        // If we don't have params, throw an error
        // TODO: Make this a proper exception
        if(!$config)
            $config = $this->getDefaultConfig();
        
        parent::__construct($config);
    }
    
    public function getPDO() {
        if(!$this->pdo) {
            $filename = realpath($this->config['filename']);
            $dsn = "sqlite:$filename";
            $this->pdo = new \PDO($dsn);
        }
        return $this->pdo;
    }
}

?>
