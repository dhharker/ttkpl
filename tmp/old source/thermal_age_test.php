#!/usr/bin/php
<?php

include ("drrc.php");

include ("util/PHP_GNUPlot.php");


/*


Test scenario:
    * Sample buried under 3.4m of soft, sandy soil then 0.3m of saturated sand in york.
    * Burial soil surface not in direct sunlight
    * Sample date 1450bp
    * Excavated in 1918 and stored at 12C +/- 2C since then in cellar or whatever


*/

$E1 = new temporothermal ();
$temps = new temperatures ();
$E1->setTempSource ($temps);

$york = new latLon (53.946780958240836, -1.0586142539978027);
$randomAlgeria = new latLon (29.249341018299514, 1.7578125);

$localisingCorrections = $temps->getPalaeoTemperatureCorrections ($york);

$E1->setLocalisingCorrections ($localisingCorrections);

$sampleAge = new palaeoTime (1450);
$excavatedTime = new palaeoTime (scalarFactory::_getAdBp (1918));
$nowTime = new palaeoTime ("NOW");
$E1->setTimeRange ($sampleAge, $excavatedTime);


$burial = new burial ();

$DhSand = scalarFactory::makeThermalDiffusivity (0.088128);
$DhSand->desc = "Saturated sand (Hukseflux)";
$sandThickness = scalarFactory::makeMetres (0.30);
$sandLayer = new thermalLayer ($sandThickness, $DhSand, "Sample found 30cm into layer of saturated sand");
$burial->addThermalLayer ($sandLayer);

$DhSandySoil = scalarFactory::makeThermalDiffusivity (0.24);
$DhSandySoil->desc = "Fresh, sandy soil. (Arya)";
$burialDepth = scalarFactory::makeMetres (3.4);
$soilLayer = new thermalLayer ($burialDepth, $DhSandySoil, "Burial conditions for in-situ phase of thermal history of example sample. Big old load of mud.");
$burial->addThermalLayer ($soilLayer);

$E1->setBurial ($burial);
$E1->setVegetationCover (FALSE, TRUE);

$E1->setChunkSize (100);

$tHist = $E1->timeWalk ();




$E2 = new temporothermal ();
$e2t = new sine ();
// this is the storage phase after excavation
$e2t->setGenericSine (scalarFactory::makeCentigradeAbs (12), scalarFactory::makeKelvinAnomaly (4), scalarFactory::makeDays (0));
$E2->setConstantClimate ($e2t);
$E2->setTimeRange ($excavatedTime, $nowTime);



$depurination = new kinetics (126940, 17745329175.856213, "Revised values for DNA depurination");

$ta = new thermalAge ();
$ta->addTemporothermal ($E1);
$ta->addTemporothermal ($E2);
$ta->setKinetics ($depurination);

$age = $ta->getAge ();
$tAge = $ta->getThermalAge ();


$ta->_nukeDataMess ();
print_r (array ($ta->teffs, $ta->ttRanges, $age, $tAge));

die ();







die();

$plot = new GNUPlot();

// $plot->set ("size 2.5/5.0, 2.5/3.5");
// $plot->set ("origin 0.5/5.0, 0.5/3.5");


$plot->setSize( 1.0, 1.0 );

// $plot->set ("tmargin 0");
//$plot->set ("rmargin 10");
//$plot->set ("bmargin 30");
// $plot->set ("lmargin 0");

$tt = '';
$tt .= "\\n";
$tt .= $tHist->numPoints . " samples at " . $E1->chunkSize . "yr interval over " . $sampleAge->getYearsBp() . " to " . $excavatedTime->getYearsBp() . "yrs. b.p.\\n";
$mrsp = round ($localisingCorrections['mean']->source[1]->regRSqPc (), 2);
$tt .= "T@$york/K = " . round ($localisingCorrections['mean']->a, 2) . " * T(global anom.) + " . round ($localisingCorrections['mean']->offset->a, 2) . " ($mrsp%)\\n";
$arsp = round ($localisingCorrections['amplitude']->source[1]->regRSqPc (), 2);
$tt .= "A(p-p)@$york/K = " . round ($localisingCorrections['amplitude']->a, 2) . " * T(global anom.) + " . round ($localisingCorrections['amplitude']->offset->a, 2) . " ($arsp%)\\n";
$tt .= "Burial(z,Dh): $burial";

$plot->setTitle($tt); 
$deq = "# days";
$dH = new PGData($deq);
foreach ($tHist->bins as $bi => $bc) {
    $dH->addDataEntry( array(($tHist->labels[$bi] + scalarFactory::kelvinOffset), $bc) ); 
}
$dM = new PGData("Local mean (abs)");
$da = new PGData("Local p-p amplitude (rel)");
$dga = new PGData("Mean global anomaly (rel, 0bp base)");
foreach ($E1->twData['mean'] as $years => $mat) {
    $dM->addDataEntry( array($years, $mat + scalarFactory::kelvinOffset) ); 
    $da->addDataEntry( array($years, $E1->twData['amp'][$years]) ); 
    $ypt = new palaeoTime ($years);
    $dga->addDataEntry( array($years, $temps->getGlobalMeanAnomalyAt($ypt)->getScalar()->getValue() )); 
    
}
$plot->plotData( $dH, 'boxes', '1:2', 'x2y2', 'fs solid 0.3 lc rgb "#D7D7D7"'); 

// $plot->plotData( $dM, 'lines', '1:2', 'x1y1', 'smooth bezier'); 
$plot->plotData( $dM, 'lines', '1:2', 'x1y1'); 
$plot->plotData( $da, 'lines', '1:2', 'x1y1'); 
$plot->plotData( $dga, 'lines', '1:2', 'x1y1'); 

//$plot->setRange('y', 0, 5);

//$plot->set ("size ratio 0.5");
$plot->set ("autoscale");
//$plot->set ("log y2");
//$plot->set ("xtics rotate by 330");
$plot->set ("nolog y");
$plot->setTics ("y", 'nomirror');
$plot->setTics ("x", 'nomirror');
$plot->setTics ("y2", 'nomirror');
$plot->setTics ("x2", 'nomirror');
$plot->set ("grid noy2tics ytics");
$plot->set ("grid nox2tics xtics");
$plot->set ('border 3 rgb "black"');

$plot->setDimLabel ("x2", "Bin temperature/C");
$plot->setDimLabel ("y2", "# days @ bin temperature");
$plot->setDimLabel ("x", "Years b.p.");
$plot->setDimLabel ("y", "Absolute or relative temperature/C @ time");
$plot->set ("key left below");
// $plot->set ("key box");
$plot->set ("size ratio 0.5");
$plot->export('thermal_age_test.png');

$plot->close();






//*/












?>
