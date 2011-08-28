<?php

namespace Fossil\Databases;

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
}

?>
