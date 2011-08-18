<?php

namespace Fossil\Renderers;

use Fossil\Responses\BaseResponse;

/**
 * Description of DevelRenderer
 *
 * @author lachlan
 * @F:Object(type = "Renderer", name = "Development")
 */
class DevelRenderer {
    public function render(BaseResponse $resp) {
        var_dump($resp);
    }
}

?>
