<?php

/**
 * Copyright (c) 2011, predakanga
 * All rights reserved.
 * 
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *     * Redistributions of source code must retain the above copyright
 *       notice, this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *     * Neither the name of the <organization> nor the
 *       names of its contributors may be used to endorse or promote products
 *       derived from this software without specific prior written permission.
 * 
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL <COPYRIGHT HOLDER> BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 * 
 * @author predakanga
 * @since 0.1
 * @category Fossil Core
 * @package Fossil
 * @subpackage Forms
 * @license https://github.com/predakanga/Fossil/blob/master/LICENSE.txt New BSD License
 */

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
            if($data['type'] == "file") {
                if(isset($_FILES[$data['fieldName']]))
                    $this->$propName = $_FILES[$data['fieldName']];
            } else {
                if(isset($request->args[$data['fieldName']]))
                    $this->$propName = $request->args[$data['fieldName']];
                else
                    $this->$propName = $data['default'];
            }
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
    
    public function isValid() {
        // TODO: Parse annotations for validation requirements
        return true;
    }
}

?>
