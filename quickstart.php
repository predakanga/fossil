<?php

use Fossil\Fossil;

require_once(__DIR__."/Fossil.php");

$core = Fossil::bootstrap(Fossil::DEVELOPMENT);
$core->run();

?>