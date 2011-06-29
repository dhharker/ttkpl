#!/usr/bin/php
<?php


/**
 * Creates CSV output file illustrating the difference between thermal age in buried and unburied
 * samples from 2011 to 100 years b.p. in northern England.
 * 
 */

include 'lib/ttkpl.php';


$anom = 

$climate = new sine();
$climate->setGenericSine(
        scalarFactory::makeCentigradeAbs(10),
        scalarFactory::makeKelvinAnomaly(12),
        scalarFactory::makeDays(0)
        );

exit (1);

$pt = new palaeoTime(1000);
echo $pt->getYearsBp() . ":";
$pt->setYearsBp(999);
echo $pt->getYearsBp() . ":";
$pt->setYearsBp(888);
echo $pt->getYearsBp() . "\n";

exit (1);

$depurination = new kinetics (126940, 17745329175.856213, "DNA depurination (bone)");
$temps = new temperatures ();
$location = new latLon (51, -1);
$localisingCorrections = $temps->getPalaeoTemperatureCorrections ($location);

$soil = new thermalLayer(scalarFactory::makeMetres(3), scalarFactory::makeThermalDiffusivity (0.1), "Hypothetical dry, peaty soil.");
$grave = new burial();
$grave->addThermalLayer($soil);

$ptA = new palaeoTime (-61);

$ttA = new temporothermal ();
$ttA->setTempSource ($temps);
$ttA->setLocalisingCorrections ($localisingCorrections);

$ttB = new temporothermal ();
$ttB->setTempSource ($temps);
$ttB->setLocalisingCorrections ($localisingCorrections);

for ($zageBp = -60; $zageBp <= 100; $zageBp += 1) {
    
    $age = new palaeoTime ($zageBp);
    $ttA->setTimeRange ($age, $ptA);
    $ttB->setTimeRange ($age, $ptA);

    $taA = new thermalAge ();
    $taA->setKinetics ($depurination);
    $taA->addTemporothermal ($ttA);
    $y = $taA->getThermalAge ();
    echo "$zageBp," . $ttA->rangeYrs . "," . $y->getValue() . ",";

    // Now bury the temporothermal (reverse the polarity of the deflector dish while we're at it) and see how things change.
    $ttB->setBurial($grave);
    $taB = new thermalAge ();
    $taB->setKinetics ($depurination);
    $taB->addTemporothermal ($ttB);
    $y = $taB->getThermalAge ();
    echo $y->getValue() . "\n";

}
