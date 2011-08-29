<?php

namespace Fossil\Forms;

/**
 * Description of PgSQLConfig
 *
 * @author predakanga
 * @F:Form(name = "PgSQLConfig")
 */
class PgSQLConfig extends BaseDriverForm {
    /** @F:FormField(fieldName = "pgsql_host") */
    public $host = "localhost";
    /** @F:FormField(fieldName = "pgsql_port") */
    public $port = 5432;
    /** @F:FormField(fieldName = "pgsql_db", label = "Database") */
    public $db;
    /** @F:FormField(fieldName = "pgsql_user") */
    public $user;
    /** @F:FormField(fieldName = "pgsql_password", type = "password", label = "Password") */
    public $pass;
}

?>
