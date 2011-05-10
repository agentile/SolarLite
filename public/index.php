<?php
$system = dirname(dirname(__FILE__));

set_include_path($system);

require "$system/core/solarlite.php";

include "$system/config/config.php";

$solarlite = new SolarLite();
$solarlite->start($config);
