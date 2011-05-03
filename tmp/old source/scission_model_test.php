#!/usr/bin/php -n
<?php
include ("drrc.php");


// Need to put real DNA figures in here eventually
define ('ST_INIT_LEN', 100);
define ('ST_INIT_COPIES', 1);

// Default to using 10C thermal years
define ('ST_TEMP_C', 20);
define ('ST_STEP_SECONDS', 60*60*24*365.25);

// Number of DP to bother generating for P() comparisons
define ('ST_RANDOM_DP', 9);

// Setup objects
$hist = new dnaHistogram ();
$time = scalarFactory::makeSeconds (ST_STEP_SECONDS);
$temp = scalarFactory::makeCentigradeAbs (ST_TEMP_C);
$depurination = new kinetics (126940, 17745329175.856213, "DNA depurination (bone)"); // units kJ/mol., sec.

// Fill the histogram with a certain number of copies of DNA molecules of a certain length
$hist->setBins (ST_INIT_LEN);
$hist->addPoint (array_fill (0, ST_INIT_COPIES, ST_INIT_LEN));

// Iterate the scission model over the histogram until the threshold is reached
do {
    kineticScissionIteration ($depurination, $hist, $time, $temp);
    //print_r ($hist);
    echo $hist->numBonds() . "\n";
//     sleep (1);
    
}
while (!scissionThresholdReached ($hist, 100));




function kineticScissionIteration (kinetics $kinetics, histogram $hist, scalar $time, scalar $temp) {
    $k = $kinetics->getRate ($temp); // unit mol.^sec-1
    $t = $time; // unit sec.
    $kt = $k->getValue () * $t->getValue(); // P(bond breakage) = $kt
    // Prepare a replacement set of histogram values (so that we don't break the same chain twice in the same interation)
    $newValues = array ();
    // Iterate molecules
    foreach ($hist->x as $molInd => $molLength) {
        // Iterate bonds
        for ($i = 1; $i < $molLength; $i++) {
            $rn = randN ();
            if ($rn <= ($kt)) {
                $newValues[] = $i;
                $newValues[] = $molLength - $i;
            }
            else
                $newValues[] = $molLength;
        }
    }
    $hist->x = $newValues;
    $hist->getBinCounts();
}

/*
Returns true unless the histogram contains no points in bins numbers above the second parameter
*/
function scissionThresholdReached (histogram $hist, $threshold = 0) {
    return (max ($hist->x) < $threshold) ? TRUE : FALSE;
}

function randN () {
    $mul = pow (10, ST_RANDOM_DP);
    return round (rand (0, $mul) / $mul, ST_RANDOM_DP);
}





?>

