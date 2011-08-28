<?php

namespace Fossil\Databases;

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
}

?>
