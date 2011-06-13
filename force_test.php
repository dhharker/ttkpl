#!/usr/bin/php
<?php

include 'lib/ttkpl.php';

$temps = new temperatures ();
$location = new latLon (45, -100);
$localisingCorrections = $temps->getPalaeoTemperatureCorrections ($location);

$ptA = new palaeoTime (scalarFactory::_getAdBp (2000));

for ($zageBp = 1000; $zageBp <= 100000; $zageBp += 1000) {
    $tt = new temporothermal ();
    $tt->setTempSource ($temps);

    $tt->setLocalisingCorrections ($localisingCorrections);
    $age = new palaeoTime ($zageBp);
    $tt->setTimeRange ($age, $ptA);
    $depurination = new kinetics (126940, 17745329175.856213, "DNA depurination (bone)");

    $taC = new thermalAge ();
    $taC->setKinetics ($depurination);
    $taC->addTemporothermal ($tt);
    $y = $taC->getThermalAge ();
    echo "$zageBp," . $y->getValue() . "\n";
}
