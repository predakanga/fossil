<?php

use Fossil\Fossil;

require_once(dirname(__FILE__)."/Fossil.php");

$core = Fossil::bootstrap(Fossil::DEVELOPMENT);
$core->run();

?>
