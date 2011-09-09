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
    
    protected $pdo;
    
    public function getPDO() {
        if(!$this->pdo) {
            $filename = realpath($this->config['filename']);
            $dsn = "sqlite:$filename";
            $this->pdo = new \PDO($dsn);
        }
        return $this->pdo;
    }
    
    protected function getDefaultConfig() {
        // Default driver, so it must have a default config
        $config = parent::getDefaultConfig();
        if(!$config) {
            return array('filename' => 'default.db');
        }
    }
}

?>
