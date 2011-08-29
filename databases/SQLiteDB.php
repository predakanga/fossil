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
        parent::__construct($config);
    }
}

?>
