<?php

namespace Fossil\Forms;

/**
 * Description of SQLiteForm
 *
 * @author predakanga
 * @F:Form(name = "SQLiteConfig")
 */
class SQLiteConfig extends BaseDriverForm {
    /** @F:FormField(label = "Filename", fieldName = "sqlite_filename") */
    public $path;
}

?>
