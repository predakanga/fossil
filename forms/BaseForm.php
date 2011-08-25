<?php

namespace Fossil\Forms;

use Fossil\OM;

/**
 * Description of BaseForm
 *
 * @author predakanga
 */
abstract class BaseForm {
    protected $form_identifier;
    protected $form_template;
    protected $form_fields;
    
    public function __construct() {
        // If form_identifier isn't set, default to the form name
        if(!$this->form_identifier) {
            $form_anno = OM::Annotations()->getClassAnnotations(get_class($this), "F:Form");
            $this->form_identifier = $form_anno[0]->name;
            $this->form_template = $form_anno[0]->template;
        }
        $this->detectFields();
        if($this->isSubmitted())
            $this->populate();
    }
    
    private function detectFields() {
        // For each property that we have, check for FormField annotations
        $reflClass = new \ReflectionClass($this);
        foreach($reflClass->getProperties() as $reflProp) {
            $annotations = OM::Annotations()->getPropertyAnnotations($reflProp, "F:FormField");
            // Should only be one
            // TODO: Double check that Doctrine's annotation layer only allows one annotation per type
            if(count($annotations)) {
                $propName = $reflProp->getName();
                $this->form_fields[$propName] = $annotations[0]->type;
            }
        }
    }
    
    private function populate() {
        // FIXME: Do we always want to populate forms from the topmost request?
        $request = OM::Dispatcher()->getTopRequest();
        // For each property that we have, check for FormField annotations
        $reflClass = new \ReflectionClass($this);
        foreach($reflClass->getProperties() as $reflProp) {
            $annotations = OM::Annotations()->getPropertyAnnotations($reflProp, "F:FormField");
            // Should only be one
            // TODO: Double check that Doctrine's annotation layer only allows one annotation per type
            if(count($annotations)) {
                $propName = $reflProp->getName();
                $fieldName = $reflProp->getName();
                if($annotations[0]->fieldName)
                    $fieldName = $annotations[0]->fieldName;
                // Finally, set the property
                $this->$propName = $request->args[$fieldName];
            }
        }
    }
    
    public function getFields() { return $this->form_fields; }
    public function getIdentifier() { return $this->form_identifier; }
    public function getTemplate() { return $this->form_template; }
    
    public function isSubmitted() {
        // FIXME: Do we always want to populate forms from the topmost request?
        $request = OM::Dispatcher()->getTopRequest();
        if(isset($request->args['form_id']) && $request->args['form_id'] == $this->form_identifier)
            return true;
        return false;
    }
    
    public function isValidSubmission() {
        // TODO: Parse annotations for validation requirements
        return true;
    }
}

?>
