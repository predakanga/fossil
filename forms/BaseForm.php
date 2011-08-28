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
                $data = array('type' => $annotations[0]->type);
                if($annotations[0]->fieldName)
                    $data['fieldName'] = $annotations[0]->fieldName;
                else
                    $data['fieldName'] = $propName;
                if($annotations[0]->type == "select")
                    $data['options'] = explode(",", $annotations[0]->options);
                if($annotations[0]->label)
                    $data['label'] = $annotations[0]->label;
                else
                    $data['label'] = ucfirst($propName);
                $data['default'] = $annotations[0]->default;
                
                $this->form_fields[$propName] = $data;
            }
        }
    }
    
    private function populate() {
        // FIXME: Do we always want to populate forms from the topmost request?
        $request = OM::Dispatcher()->getTopRequest();
        // For each property that we have, check for FormField annotations
        foreach($this->form_fields as $propName => $data) {
            if(isset($request->args[$data['fieldName']]))
                $this->$propName = $request->args[$data['fieldName']];
            else
                $this->$propName = $data['default'];
        }
    }
    
    public function getFields() { return $this->form_fields; }
    public function getFieldOptions($field) { return $this->form_fields[$field]['options']; }
    public function setFieldOptions($field, $options) { $this->form_fields[$field]['options'] = $options; }
    public function getIdentifier() { return $this->form_identifier; }
    public function getTemplate() { return $this->form_template; }
    
    public function isSubmitted() {
        // FIXME: Do we always want to populate forms from the topmost request?
        $request = OM::Dispatcher()->getTopRequest();
        if(!isset($request->args['form_id']))
            return false;
        if(is_array($request->args['form_id']) && in_array($this->form_identifier, $request->args['form_id']))
            return true;
        elseif(!is_array($request->args['form_id']) && $request->args['form_id'] == $this->form_identifier)
            return true;
        return false;
    }
    
    public function isValidSubmission() {
        // TODO: Parse annotations for validation requirements
        return true;
    }
}

?>
