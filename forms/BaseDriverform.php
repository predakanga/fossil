<?php

namespace Fossil\Forms;

use Fossil\Interfaces\IDriverForm;

/**
 * Description of BaseDriverform
 *
 * @author predakanga
 */
class BaseDriverForm extends BaseForm implements IDriverForm {
    public function toConfig() {
        $toRet = array();
        foreach($this->form_fields as $name => $data) {
            $toRet[$name] = $this->$name;
        }
        return $toRet;
    }
}

?>
