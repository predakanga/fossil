<?php

namespace Fossil\Forms;

/**
 * Description of DriverSelection
 *
 * @author lachlan
 * @F:Form(name = "DriverSelection")
 */
class DriverSelection extends BaseForm {
    /**
     * @F:FormField(type="select")
     */
    public $cacheDriver;
    /**
     * @F:FormField(type="select", options="MySQL,PgSQL,SQLite")
     */
    public $dbDriver;
    /**
     * @F:FormField(type="select")
     */
    public $templateDriver;
}

?>
