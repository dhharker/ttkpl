#!/usr/bin/php
<?php

include ("data_interfaces.php");

$t = new temporalDatum (6000);
$s = new spatialDatum (array (51.041, -0.0352));

print_r ($t);

?>