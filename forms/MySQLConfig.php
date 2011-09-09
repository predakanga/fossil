<?php

namespace Fossil\Forms;

/**
 * Description of MySQLConfig
 *
 * @author predakanga
 * @F:Form(name = "MySQLConfig")
 */
class MySQLConfig extends BaseDriverForm {
    /** @F:FormField(fieldName = "mysql_host") */
    public $host = "localhost";
    /** @F:FormField(fieldName = "mysql_port") */
    public $port = 3306;
    /** @F:FormField(fieldName = "mysql_db", label = "Database") */
    public $dbname;
    /** @F:FormField(fieldName = "mysql_user") */
    public $user;
    /** @F:FormField(fieldName = "mysql_password", type = "password", label = "Password") */
    public $password;
}

?>
