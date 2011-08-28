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
     * @F:FormField(type="select", label="Cache driver")
     */
    public $cacheDriver;
    /**
     * @F:FormField(type="select", label="DB driver")
     */
    public $dbDriver;
    /**
     * @F:FormField(type="select", label="Template driver")
     */
    public $templateDriver;
}

?>
