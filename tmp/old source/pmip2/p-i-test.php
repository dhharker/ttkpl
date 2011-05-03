#!/usr/bin/php
<?php

include ("pmip_import.php");

$p = new PMIP2 ();

print_r ($p->getTemps (51, -1));

?>

