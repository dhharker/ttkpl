#!/usr/bin/php
<?php

include ("drrc.php");

include ("util/drrcReport.php");

/*


Test scenario:
    * Sample buried under 3.4m of soft, sandy soil then 0.3m of saturated sand in york.
    * Burial soil surface not in direct sunlight
    * Sample date 1450bp
    * Excavated in 1918 and stored at 12C +/- 2C since then in cellar or whatever


*/
$kins = array ();
$kins[] = new kinetics (126940, 17745329175.856213, "DNA Depurination (Bone)");
$kins[] = new kinetics (126940, 604291834613.532, "DNA Depurination (Hair)");
$kins[] = new kinetics (173207.8723, 2.11E19, "Collagen Gelatinisation");
$kins[] = new kinetics (113000.0, 3.97E8, "Amino Acid Racemisation");

$plot = new GNUPlot();

$plot->setSize( 1.0, 1.0 );

$tt = 'Kinetics test';
$plot->setTitle($tt); 
$da = array ();

$TLow = -20;
$THigh = 40;

foreach ($kins as $ki => $kin) {
    $da[$ki] = new PGData($kin->desc);
    for ($T = $TLow; $T <= $THigh; $T++) {
        $da[$ki]->addDataEntry (array ($T, $kin->getRate (scalarFactory::makeKelvin ($T - scalarFactory::kelvinOffset) )->getValue() ));
    }
    $plot->plotData( $da[$ki], 'lines', '1:2', 'x1y1'); 
}



$plot->set ("autoscale");

$plot->set ("log y");
$plot->setTics ('x', 'nomirror');
$plot->setTics ('y', 'nomirror');
$plot->setDimLabel ("x", "Temperature (C)");
$plot->setDimLabel ("y", "Rate of reaction (moles per second)");
$plot->set ("key left below");

$plot->set ("size ratio 0.5");
$plot->export('temp_rate_test.png');

$plot->close();






//*/












?>
