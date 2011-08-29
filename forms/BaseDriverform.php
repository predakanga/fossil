<?php

namespace Fossil\Forms;

use Fossil\Interfaces\IDriverForm;

/**
 * Description of BaseDriverform
 *
 * @author predakanga
 */
class BaseDriverform extends BaseForm implements IDriverForm {
    public function toConfig() {
        // Simple, naïve code
        $toRet = array();
        foreach($this->form_fields as $name => $data) {
            $toRet[$data['fieldName']] = $this->$name;
        }
        return $toRet;
    }
}

?>
