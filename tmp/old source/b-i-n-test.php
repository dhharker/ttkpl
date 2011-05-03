#!/usr/bin/php
<?php

include ("drrc.php");

$b = new bintanja ();

$ts = array (0,100000,124125,11119,4324);

//foreach ($ts as $t) {
for ($t = 0; $t < 1500000; $t += 50) {
    $t = round ($t, 1);
    $it = new palaeoTime ($t);
    $v = $b->getInterpolatedValueFromFacet ($it);
    $num = $v->getScalar()->getValue();
    
    echo ($num != 0) ? $t . " b.p. = " . $num . " deg. C offset\n" : ".";
}


?>