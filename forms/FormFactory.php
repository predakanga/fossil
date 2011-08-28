<?php

namespace Fossil\Forms;

use Fossil\OM;

/**
 * Description of FormFactory
 *
 * @author predakanga
 * 
 * @F:Object("Form")
 */
class FormFactory {
    private $knownForms;
    private $formInstances = array();
    
    public function __construct() {
        $this->knownForms = array();
        foreach(OM::Annotations()->getClassesWithAnnotation("F:Form") as $className) {
            $anno = OM::Annotations()->getClassAnnotations($className, "F:Form");
            $this->knownForms[$anno[0]->name] = $className;
        }
    }
    
    public function get($formName) {
        if(!isset($this->formInstances[$formName]))
                $this->formInstances[$formName] = new $this->knownForms[$formName];
        return $this->formInstances[$formName];
    }
    
    public function getSubmittedForms() {
        
    }
}

?>
