#!/usr/bin/php
<?php

include ("drrc.php");

include ("util/drrcReport.php");
include ("util/drrcAppSpec.php");




/*
Feldhofer 2
51.226, 6.951
found 1856
constant storage temp of 15C
analysed 1999
age 39240
under 10m Generic rock 0.0864 (Wikipedia)

*/
$temps = new temperatures ();
$ttCave = new temporothermal ();
$ttCave->setTempSource ($temps);

// Where
$fh2 = new latLon (51.226, 6.951);

// Localising corrections
$localisingCorrections = $temps->getPalaeoTemperatureCorrections ($fh2);
$ttCave->setLocalisingCorrections ($localisingCorrections);

// Buried timerange
$age = new palaeoTime (1000000);//39240);
$found = new palaeoTime (scalarFactory::_getAdBp (1856));
$ttCave->setTimeRange ($age, $found);

// Cave definition and thermal buffering
$burial = new burial ();
$rock = scalarFactory::makeThermalDiffusivity (0.0864);
$rock->desc = "Generic 'rock' (Wikipedia)";
$rockDepth = scalarFactory::makeMetres (10.0);
$cave = new thermalLayer ($rockDepth, $rock, "Assumed value for cave samples");
$burial->addThermalLayer ($cave);
$ttCave->setBurial ($burial);

// Storage timerange & temperature after excavation
$analysed = new palaeoTime (scalarFactory::_getAdBp (1999));
$ttStorage = new temporothermal ();
$storageSine = new sine ();
$storageSine->setGenericSine (scalarFactory::makeCentigradeAbs (15), scalarFactory::makeKelvinAnomaly (0), scalarFactory::makeDays (0));
$ttStorage->setConstantClimate ($storageSine);
$ttStorage->setTimeRange ($found, $analysed);

// Reaction
$depurination = new kinetics (126940, 17745329175.856213, "DNA depurination (bone)");

// Showtime!
$ta = new thermalAge ();
$ta->setKinetics ($depurination);
$ta->addTemporothermal ($ttCave);
$ta->addTemporothermal ($ttStorage);

// Chunk sizes
foreach ($ta->temporothermals as $ti => $tt)
    echo "TT$ti chunk yrs = " . $tt->autoChunkSize () . "\n";

// Output
$a = round ($ta->getAge());
$c = round ($ta->getThermalAge ()->getValue());
$kt = round ($ta->getThermalAge ()->ktSec, 8);
$t = round (scalarFactory::makeCentigradeAbs ($ta->teffs[0])->getValue(), 2);

echo "Age $a years. Thermal age $c 10C thermal years. Lambda $kt. Effective temperature during cave burial: $t degrees C.\n";

$plot = new drrcPlot ("Feldhofer 2");
$plot->labelAxes ("years bp.", "abs. temp/deg C", "", "rel. temp/deg C");
$plot->setGrid (array ('x', 'y2'));
foreach ($ta->temporothermals as $ti => $tt) {
     
//     $plot->sada ($tt->twData['amp'], "[$ti]Local p-p amplitude/deg K rel.", "x1y2");
    if (isset ($tt->twData['TGraph'])) {
        $plot->sada ($tt->twData['TGraph']['surface'], "[$ti] Local surface annual temperature ranges/deg C abs.", "x1y1", "filledcu lc rgb \"#6FCBE3\"", '1:2:3', $extra='');
        $plot->sada ($tt->twData['TGraph']['buried'], "[$ti] Local buffered annual temperature ranges/deg C abs.", "x1y1", "filledcu lc rgb \"#E3C66F\"", '1:2:3');
        $plot->sada ($tt->twData['mean'], "[$ti] Local MAT/deg C abs.", "x1y1", "lines lc rgb \"#88293A\"");
    
    }
    if (isset ($tt->twData['ganom']))
        $plot->sada ($tt->twData['ganom'], "[$ti] Global anomaly/deg C rel.", "x1y2", "lines lc rgb \"#1D8F39\"");
}

$plot->plot ("Feldhofer2.png");


die ();






?>
