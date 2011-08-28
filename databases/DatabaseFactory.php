<?php

namespace Fossil\Databases;

/**
 * Description of DatabaseFactory
 *
 * @author predakanga
 * @F:Object("Database")
 */
class DatabaseFactory {
    public function __construct() {
        // Find the correct DB layer, select it, and throw a SelectionChangedException
        $dbName = OM::Settings("Fossil", "db");
        if(!$dbName) {
            $dbName = "SQLite";
        } else {
            $dbName = $dbName["driver"];
        }
        OM::select("Database", $dbName);
        throw new \Fossil\Exceptions\SelectionChangedException();
    }
}

?>
