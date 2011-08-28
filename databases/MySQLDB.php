<?php

namespace Fossil\Databases;

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
}

?>
