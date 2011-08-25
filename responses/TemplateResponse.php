<?php

namespace Fossil\Responses;

use Fossil\OM;

/**
 * Description of DataResponse
 *
 * @author predakanga
 */
class TemplateResponse extends RenderableResponse {
    private $templateName;
    private $templateData;
    
    public function __construct($template, $args = array()) {
        $this->templateName = $template;
        $this->templateData = $args;
    }
    
    public function render() {
        parent::render();
        OM::Renderer()->render($this->templateName, $this->templateData);
    }
}

?>
