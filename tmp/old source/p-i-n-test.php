#!/usr/bin/php
<?php

// include ("pmip_import_normalised.php");

$p = new pmip ();

$s1 = new latLon (89.01, -179.09);

print_r ($p->getRealValueFromFacet ($s1));


?>