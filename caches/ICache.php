<?php

namespace Fossil\Caches;

/**
 *
 * @author lachlan
 */
interface ICache {
    public function has($key);
    public function get($key);
    public function set($key, $value);
    public function update($key, $update_cb);
}

?>
