<?php

define ('DRRC_VERSION_STRING', 'production v. 0.3 alpha');

include ("data_interfaces.php");

include ("bintanja_import_normalised.php");
include ("pmip_import_normalised.php");


if (!function_exists ('log_message')) { function log_message ($a='',$s='',$d='',$f='',$g='') {return true;} }

abstract class correction {
    abstract public function correct ($ftVal);
    public $desc = "Undescribed!";
    public $source = NULL; // where have the coefficient values for this correction come from?
    public function describe ($t = NULL) {
        return ($t === NULL) ? $this->desc : ($this->desc = $t);
    }
}

class offsetCorrection extends correction {
    public $a = 0;
    
    public function __construct ($val) {
        $this->a = (float) $val;
    }
    public function correct ($ftVal) {
        return (float) $ftVal + (float) $this->a;
    }
    
}
/*
(?)'first order' polynomial; 
y = ax + b

$pc = new proportionalCorrection ($a, $b);
$x = 100;
$y = $pc->correct ($x);

*/
class deg1Polynomial extends correction {
    public $a = 0;
    public $offset = NULL;
    public function __construct ($val, $offset) {
        $this->offset = new offsetCorrection ($offset);
        $this->a = (float) $val;
    }
    public function correct ($ftVal) {
        if (is_object ($ftVal) && get_class ($ftVal) == 'scalar')
            $v = $ftVal->getScalar()->getValue();
        elseif (is_object ($ftVal))
            $v = $ftVal->getValue();
        else
            $v = $ftVal;
        return ((float) $v * (float) $this->a) + $this->offset->a;
    }
}
class deg2Polynomial extends correction {
    public $a = 0;
    public $deg1 = NULL;
    public function __construct ($m2, $m, $offset) {
        $this->deg1 = new deg1Polynomial ($m, $offset);
        $this->a = (float) $m2;
    }
    public function correct ($ftVal) {
        return (pow ((float) $ftVal, 2) * (float) $this->a) + $this->deg1->correct ($ftVal);
    }
}

// abstract class chainable {
//     
//     public $value;
//     
//     public function getValue () {
//         return (is_object ($this->value)) ? $this->value->getValue () : $this->value;
//     }
//     
//     public $corrections = array ();
//     
//     public function addCorrection (correction $cor) {
//         $this->corrections[] = $cor;
//     }
//     public function correctValue ($v = NULL) {
//         $v = ($v === NULL) ? $this->getValue () : $v;
//         foreach ($this->corrections as $cor)
//             $v = $cor->correct ($v);
//         return $v;
//     }
//     
// }

// Wu & Nofziger
// This contains all the stuff common to equations in this model
abstract class wuno {
    
    const yearLength = 365;
    
    static function dampingDepth (scalar $thermalDiffusivity) {
        return pow (2 * $thermalDiffusivity->getValue () / self::annualFluctuationFrequency (), 0.5);
    }
    static function annualFluctuationFrequency () {
        return 2 * pi () / self::yearLength;
    }
    
}

class sine extends wuno {
    // period is *1* to sine::period, NOT 0 based!(!)
    public $period = 365;
    public $periodOf = "days";
    public $periodName = "year";
    
    // Offset from time=1 of value minimum in one cycle
    public $minOffset = 0;
    // Peak-peak amplitude of same units as value
    public $ampltidue = 0;
    // Mean value
    public $mean = 0;
    
    public function setGenericSine ($mean, $amp, $off) {
        $m = new datum ();
        $m->setValue (scalarFactory::makeKelvin ($mean));
        $a = new datum ();
        $a->setValue (scalarFactory::makeKelvinAnomaly ($amp));
        $o = new datum ();
        $o->setValue (scalarFactory::makeDays ($off));
        
        $this->setSine ($m, $a, $o);
    }
    public function setSine (datum $mean, datum $amplitude, datum $minOffset) {
        $this->parentValues = array (
            'mean'          => $mean,
            'amplitude'     => $amplitude,
            'minOffset'     => $minOffset,
        );
        $this->mean         = $mean->getScalar()->getValue();
        $this->amplitude    = $amplitude->getScalar()->getValue();
        $this->minOffset    = $minOffset->getScalar()->getValue();
        
        $this->update ();
    }
    // Used to recalculate figures after one of the inputs has changed or when first initialising the object
    public function update () {
        $this->Ta = $this->mean;
        $this->A0 = $this->amplitude / 2;
        $this->t0 = $this->minOffset;
    }
    public function getValue ($offset = 0) {
        // (-1 because period is 1 based, offset is 0 based)
        $offset %= $this->period - 1;
        return $this->Ta + $this->A0 * sin ((2 * pi() * ($offset - $this->minOffset)) / $this->period );
    }
    function __toString () {
        return "{$this->Ta}+/-{$this->A0}";
    }
}

class burial extends wuno {
    
    public $thermalLayers = array ();
    
    public function addThermalLayer (thermalLayer $l) {
        $this->thermalLayers[] = $l;
    }
    
    public function getBufferedSine (sine $surface) {
        
        if (count ($this->thermalLayers) == 0) {
            return $surface;
        }
        else {
            $wksn = clone $surface;
            
            foreach ($this->thermalLayers as $li => $tl) {
                $nAmp = $wksn->A0 * exp (-$tl->z->getValue() / self::dampingDepth ($tl->Dh));
                //$nasc = scalarFactory::makeKelvinAnomaly ($nAmp);
                $newAmp = clone $wksn->parentValues['amplitude'];
                $newAmp->desc = "Buffered amplitude";
                $newAmp->setScalar ($nAmp * 2); 
                
                $newSine = clone $wksn;
                $newSine->parentValues['amplitude'] = array ($wksn->parentValues['amplitude'], $tl);
                $newSine->desc = "Thermally buffered temperature sine (layer " . $li+1 . ")";
                $newSine->setSine ($newSine->parentValues['mean'], $newAmp, $newSine->parentValues['minOffset']);
                $wksn = $newSine;
//     echo "intermediate ampl $li: " . $newAmp->getScalar()->getValue() / 2 . "\n";
            }
            return $newSine;
        }
        
    }
    function __toString () {
        $ld = array ();
        foreach ($this->thermalLayers as $l)
            $ld[] = "$l";
        return implode ("+", $ld);
    }
    
    
    
}


class thermalAge {
    
    public $temporothermals = array ();
    public $refTempC = 10;
    public $kinetics;
    public $rehash = TRUE;
    
    private $histograms = array ();
    private $rates = array ();
    
    public function addTemporothermal (temporothermal $tt) {
        $this->temporothermals[] = $tt;
        $this->rehash = TRUE;
    }
    public function setKinetics (kinetics $k) {
        $this->kinetics = $k;
        $this->rehash = TRUE;
    }
    
    public function getAge () {
        $yrs = 0;
        if (count ($this->temporothermals) == 0)
            return 0;
        foreach ($this->temporothermals as $tt)
            $yrs += $tt->rangeYrs;
        return $yrs;
    }
    
    public function getTeff () {
        return $this->getTempAtRate (scalarFactory::makeMolesPerSecond ($this->getKSec ()));
    }
    public function getKSec () {
        return $this->getLambda () / scalarFactory::makeSeconds (scalarFactory::makeYears ($this->getAge ()))->getValue();
    }
    public function getKYear () {
        return $this->getLambda () / $this->getAge ();
    }
    function getLambda () {
        return $this->ktSum;
    }
    
    public function getRefTemp () {
        return scalarFactory::makeCentigradeAbs ($this->refTempC);
    }
    public function getRefRate () {
        return $this->kinetics->getRate ($this->getRefTemp ());
    }
    public function getRate (scalar $T) {
        return $this->kinetics->getRate ($T);
    }
    public function getTempAtRate (scalar $k) {
        return $this->kinetics->getTempAtRate ($k);
    }
    public function getTeffFromHistogram (histogram $h) {
        // ensure histogram labels are midpoints
        $h->getBinCounts (0);
        $mps = $h->labels;
        $days = $h->bins;
        $numDaysInHist = array_sum ($days);
        array_walk ($mps, function (&$v) {$v += 0.0;});
        $ktSum = 0;
        foreach ($mps as $mpi => $mpT) {
            $ktSum += ($this->getRate (scalarFactory::makeKelvin ($mpT))->getValue ()) * $days[$mpi];
        }
        $meanRate = scalarFactory::makeMolesPerSecond ($ktSum / $numDaysInHist);
        $Teff = $this->getTempAtRate ($meanRate);
        return $Teff;
        
    }
    public function getTeffFromSine (sine $s) {
        for ($d = 1; $d <= $s->period; $d++) {
            $sumR += $this->getRate ($s->getValue ($d));
            $sumD += 1;
        }
        $meanK = scalarFactory::makeMolesPerSecond ($sumR / $sumD);
        $Teff = $this->getTempAtRate ($meanK);
        return $Teff;
    }
    public function getThermalAge () {
        log_message ('debug', " * GTA: Start.");
        if (!$this->rehash ()) {
            log_message ('debug', " * GTA: Returning without rehash.");
            return $this->lastTaScr;
        }
        log_message ('debug', " * GTA: Begin in ernest:");
        $this->thermalAges = array ();
        $this->kt = array ();
        $this->thermalAge = null;
        $this->ktSum = 0;
        $thermalAge = 0;
        log_message ('debug', " * GTA: Setup. Getting refRate and iterating Teffs... (breakage soon if not already)");
        $refRate = $this->getRefRate ();
        foreach ($this->teffs as $ti => $Teff) {
            $sampleRate = $this->getRate ($Teff);
            $time = $this->temporothermals[$ti]->rangeYrs;
            $this->kt[$ti] = $sampleRate->getValue() * scalarFactory::makeSeconds (scalarFactory::makeYears ($time))->getValue();
            $tage = $time * ($sampleRate->getValue() / $refRate->getValue());
            $thermalAge += $tage;
            $this->thermalAges[$ti] = scalarFactory::make10CThermalYears ($tage);
        }
        log_message ('debug', " * GTA: Got this far? Summing kt:");
        $this->ktSum = array_sum ($this->kt);
        log_message ('debug', " * GTA: done. Making thermal years:");
        $scr = scalarFactory::make10CThermalYears ($thermalAge);
        log_message ('debug', " * GTA: done. Set scalar property:");
        $scr->ktSec = $this->ktSum;
        log_message ('debug', " * GTA: done. Attach scalar:");
        $this->lastTaScr = $scr;
        log_message ('debug', " * GTA: done. Returning.");
        return $scr;
    }
    
    public function rehash () {
        if ($this->rehash == FALSE)
            return FALSE;
        log_message ('debug', " * rehash: Rehashing for real:");
        foreach ($this->temporothermals as $ti => $tt) {
            log_message ('debug', " * rehash: timewalk now.");
            $this->histograms[$ti] = $tt->timeWalk ();
            log_message ('debug', " * rehash: get teff from histo now.");
            $this->teffs[$ti] = $this->getTeffFromHistogram ($this->histograms[$ti]);
            log_message ('debug', " * rehash: get range now.");
            $this->ttRanges[$ti] = $tt->rangeYrs;
        }
        
        $this->rehash = FALSE;
        return TRUE;
    }
    
    function _nukeDataMess () {
        foreach ($this->temporothermals as &$tt)
            $tt->temperatures = NULL;
    }
    public function __construct () {
        
    }
    
}


/*
constants for hacking around with pmip2:
    PMIP2::
        T_LGM_21KA = "21k";
        T_MID_HOLOCENE_6KA = "6k";
        T_PRE_INDUSTRIAL_0KA = "0k";
        TMIN_VAR = 'tasmin';
        TMAX_VAR = 'tasmax';
        MODEL_HADCM3M2 = 'HadCM3M2';
*/
class temperatures {
    public $pmipIdx = array ();
    public $bintanja = NULL;
    public $whens = array ();
    
    public function __construct () {
        $this->_initDataSources ();
    }
    public function _initDataSources () {
        
        // Bintanja Data
        $this->bintanja = new bintanja ();
        
        // PMIP2 Data
        $this->whens = array (PMIP2::T_PRE_INDUSTRIAL_0KA, PMIP2::T_MID_HOLOCENE_6KA, PMIP2::T_LGM_21KA);
        $vars = array (PMIP2::TMAX_VAR, PMIP2::TMIN_VAR);
        $models = array (PMIP2::MODEL_HADCM3M2);
        foreach ($models as $model)
            foreach ($this->whens as $when)
                foreach ($vars as $var)
                    $this->pmipIdx[$model][$when][$var] = new pmip ($var, $when, $model);
    }
    
    /*
    Returns 
    */
    function getPalaeoTemperatureCorrections (facet $where) {
        
        
        // Get temps at each time for given location and global anomaly
        $localTemps = array ();
        $globalTemps = array ();
        $xyTemps = array (); // for input to linear regression class
        foreach ($this->whens as $ptc) { // ptc = pmip time constant in PMIP2::T_BLAH
            // In absolute degrees kelvin
            //print_r ($where);
            $localTemps[$ptc] = $this->getLocalMeanTempAt ($where, $ptc);
            $localAmps[$ptc] = $this->getLocalAmplitudeAt ($where, $ptc);
            $when = pmip::ptcToPalaeoTime ($ptc);
            // In degrees kelvin offset from northern hemisphere 40-80lat mean land surface temperature
            $globalTemps[$ptc] = $this->getGlobalMeanAnomalyAt ($when);
            $xyTemps['yT'][] = $localTemps[$ptc]->getScalar ()->getValue ();
            $xyTemps['yA'][] = $localAmps[$ptc]->getScalar ()->getValue ();
            $xyTemps['x'][] = $globalTemps[$ptc]->getScalar ()->getValue ();
        }
        
        
        // How local mean temperature varies with global mean temperature
        $meanLR = new linearRegression ($xyTemps['x'], $xyTemps['yT']);
        // How local amplitude varies with global mean temperature
        $ampLR = new linearRegression ($xyTemps['x'], $xyTemps['yA']);
        // How local amplitude varies with local mean temperature
        // This relationship is less strong than the above
        //$ampLR2 = new linearRegression ($xyTemps['y1'], $xyTemps['y2']);
        
        $meanCorrection = new deg1Polynomial ($meanLR->bfA(), $meanLR->bfB());
        $meanCorrection->source = array ($where, $meanLR);
        $meanCorrection->describe ("Corrects global temperature anomaly to local mean absolute temperature in degrees Kelvin.");
        
        $ampCorrection = new deg1Polynomial ($ampLR->bfA(), $ampLR->bfB());
        $ampCorrection->source = array ($where, $ampLR);
        $ampCorrection->describe ("Corrects global temperature anomaly to local annual (peak-peak) temperature amplitude in degrees Kelvin.");
        
        return array (
            'mean' => $meanCorrection,
            'amplitude' => $ampCorrection,
        );
        
    }
    
    function getGlobalMeanAnomalyAt (facet $when) {
        return $this->bintanja->getInterpolatedValueFromFacet ($when);
    }
    function _wrapLocalPmipDatum (scalar $sc, facet $where, $pmipTimeConst = PMIP2::T_PRE_INDUSTRIAL_0KA, $model = PMIP2::MODEL_HADCM3M2) {
        return new spatialDatum ($where, new temporalDatum (pmip::ptcToPalaeoTime ($pmipTimeConst), $sc));
    }
    function getLocalSineAt (facet $where, $pmipTimeConst = PMIP2::T_PRE_INDUSTRIAL_0KA, $model = PMIP2::MODEL_HADCM3M2) {
        $sine = new sine ();
        $mean = $this->_wrapLocalPmipDatum ($this->getLocalMeanTempAt ($where, $pmipTimeConst, $model),$where, $pmipTimeConst, $model);
        $amplitude = $this->_wrapLocalPmipDatum ($this->getLocalAmplitudeAt ($where, $pmipTimeConst, $model),$where, $pmipTimeConst, $model);
        $offset = $this->_wrapLocalPmipDatum (scalarFactory::makeDays (0),$where, $pmipTimeConst, $model);
        
        $sine->setSine ($mean, $amplitude, $offset);
        return $sine;
    }
    function getLocalMeanTempAt (facet $where, $pmipTimeConst = PMIP2::T_PRE_INDUSTRIAL_0KA, $model = PMIP2::MODEL_HADCM3M2) {
        $min = $this->pmipIdx[$model][$pmipTimeConst][PMIP2::TMIN_VAR]->getInterpolatedValueFromFacet ($where);
        $max = $this->pmipIdx[$model][$pmipTimeConst][PMIP2::TMAX_VAR]->getInterpolatedValueFromFacet ($where);
        
        $mean = datum::mean (array ($min, $max));        
        return $mean;
    }
    
    function getLocalAmplitudeAt (facet $where, $pmipTimeConst = PMIP2::T_PRE_INDUSTRIAL_0KA, $model = PMIP2::MODEL_HADCM3M2) {
        $min = $this->pmipIdx[$model][$pmipTimeConst][PMIP2::TMIN_VAR]->getInterpolatedValueFromFacet ($where);
        $max = $this->pmipIdx[$model][$pmipTimeConst][PMIP2::TMAX_VAR]->getInterpolatedValueFromFacet ($where);
        
        $peakToPeakTemperatureAmplitude = datum::difference ($max, $min);
        return $peakToPeakTemperatureAmplitude;
    }
    
    
}


/*
One class to rule them all
*/
class temporothermal {
    
    public $startDate; // more recent, default to present day
    public $stopDate; // less recent
    public $meanCorrection;
    public $ampCorrection;
    public $vegCorrection;
    public $burialCorrection;
    public $kinetics;
    public $rangeYrs; // num of years in this temporothermal
    
    public $chunkSize = 50;
    public $constantClimate = FALSE;
    public $constantSine;
    
    private $corrections = array (); // array of correction objects which get sequentially applied
    public $temperatures; // object with data access and stuff
    private $date; // contains the palaeotime from which interped sine values are pulled
    private $wsSine; // working start sine (e.g. global temps)
    public $intermediateSines = array ();
    
    function __construct () {
        
    }
    function timeWalk ($numBins = -1, $xText = 0) {
        $bpStart = $this->startDate->getYearsBp ();
        $bpStop = $this->stopDate->getYearsBp ();
        $tHist = new histogram ();
        $this->twData = array ();
        log_message ('debug', " * timeWalk: doing the timewalk:");
        for ($years = $bpStart; $years < $bpStop; $years += $this->chunkSize) {
            $this->setDate (new palaeoTime ($years));
            $ts = $this->tempsFromWsSine ();
            $tHist->addPoint ($ts);
            
            // Graphing stuff
            if (isset ($this->intermediateSines[1])) {
                $as0 = $this->intermediateSines[0]->A0;
                $as1 = $this->intermediateSines[1]->A0;
                $m = $this->intermediateSines[0]->Ta + scalarFactory::kelvinOffset;
                // array (surface max, min, buried max, min);
                $surf = array ($years, $m + $as0, $m - $as0);
                $turf = array ($years, $m + $as1, $m - $as1);
                $this->twData['TGraph']['surface'][$years] = $surf;
                $this->twData['TGraph']['buried'][$years] = $turf;
                $this->twData['mean'][$years] = $this->wsSine->mean + scalarFactory::kelvinOffset;
                
    //             $this->twData['amp'][$years] = $this->wsSine->amplitude;
            }
            if (isset ($this->gtc))
                $this->twData['ganom'][$years] = $this->gtc->getValue();
            
        }
        $tHist->setBins ($numBins);
        $tHist->getBinCounts ($xText);
        return $tHist;
    }
    function tempsFromWsSine () {
        $s = $this->wsSine;
        $temps = array ();
        for ($d = 1; $d <= $s->period; $d++) {
            $temps[] = $s->getValue ($d);
        }
        return $temps;
    }
    function setDate (palaeoTime $d) {
        $this->date = $d;
        if ($this->constantClimate == TRUE) {
            $this->wsSine = $this->constantSine;
        }
        else {
            $this->gtc = $this->temperatures->getGlobalMeanAnomalyAt ($d)->getScalar ();
            $this->setSineFromGlobal ($this->gtc);
        }
    }
    function setSineFromGlobal (scalar $ganom) {
        if ($this->constantClimate == TRUE)
            return TRUE;
        $this->intermediateSines = array ();
        $newSine = new sine ();
        // localising
        if (is_object ($this->meanCorrection) && is_object ($this->ampCorrection)) {
            $lm = $this->meanCorrection->correct ($ganom);
            if (is_object ($this->vegCorrection))
                $lm = $this->vegCorrection->correct ($lm);
            
            $la = $this->ampCorrection->correct ($ganom);
            $newSine->setGenericSine ($lm, $la, 0);
            $this->intermediateSines[] = clone $newSine;
            $this->wsSine = $newSine;
        }
        else {
            return FALSE;
        }
        
        if (isset ($this->burial) && is_object ($this->burial)) {
            $this->wsSine = $this->burial->getBufferedSine ($this->wsSine);
            $this->intermediateSines[] = clone $this->wsSine;
        }
        
    }
    function setConstantClimate (sine $annual) {
        $this->constantSine = $annual;
        $this->constantClimate = TRUE;
    }
    function setBurial (burial $b) {
        $this->burial = $b;
    }
    function setTempSource (temperatures $t) {
        $this->temperatures = $t;
    }
    function setChunkSize ($chYears) {
        $this->chunkSize = $chYears;
    }
    function autoChunkSize ($l = 1, $u = 1000) {
        $a = $this->rangeYrs;
        // $a / ~500 seems a good value for final results
        // 100 for test
        $c = round ($a / 500);
        $c = ($c > $u) ? $u : $c;
        $c = ($c < $l) ? $l : $c;
        $this->setChunkSize ($c);
        return $c;
    }
    function setKinetics (kinetics $kin) {
        $this->kinetics = $kin;
    }
    function setLocalisingCorrections ($arrLC) {
        $this->meanCorrection = $arrLC['mean'];
        $this->ampCorrection = $arrLC['amplitude'];
    }
    function setVegetationCover ($cover = FALSE, $known = FALSE) {
        /*
        (c == 0 && k == 1) correction += 2 deg C
        this is unshifted onto the beginning
        */
        $doit = ($cover == FALSE && $known == TRUE) ? TRUE : FALSE;
        $offset = ($doit) ? 2.0 : 0.0;
        $td = $this->_dvc ($cover, $known);
        $desc = ($doit) ? "Temperature offset of $offset C applied ($td)." : "No temperature correction applied for vegetation cover ($td).";
        $c = new offsetCorrection ($offset);
        $c->describe ($desc);
        $this->vegCorrection = $c;
    }
    function _dvc ($c, $k) {
        if ($k == TRUE)
            return ($c) ? "veg cover" : "no veg cover";
        else
            return "veg cover unknown";
    }
    function setTimeRange (palaeoTime $t1, palaeoTime $t2) {
        if ($t1->getYearsBp() > $t2->getYearsBp()) {
            $older = $t1;
            $younger = $t2;
        }
        else {
            $older = $t2;
            $younger = $t1;
        }
        $this->startDate = $younger;
        $this->stopDate = $older;
        $this->_updateRange ();
    }
    function _updateRange () {
        $this->rangeYrs = $this->startDate->distanceTo ($this->stopDate);
        
    }
    
    // bintanja
    // g>l temp correction
    // g>a temp correction
    // vegetation cover correction
    // burial layer corrections
    // start date
    // end date
    
}



// modified from original PrediCtoR codebase

class cal {
    // Return sum of values in an array
    public static function sum ($values) {
        //print_r ($values);
        $values = (array) $values;
        $sum = 0;
        foreach ($values as $value)
            $sum += $value;
        return $sum;
    }
    // Return mean of input (array) values
    public static function mean ($values) {
        if (count ($values))
            return self::sum ($values) / count ($values);
        else
            return 0;
    }
    public static function dif ($a,$b) {
        // Return absolute difference between a and b
        return ($a >= $b) ? ($a - $b) : ($b - $a);
    }
    // Take array of values and calculate standard deviation
    public function stddev ($values) {
        // Get the mean of the values
        $mean = self::mean ($values);
        // Find difference from mean of each value
        $mdiffs = array ();
        foreach ($values as $value) {
            $mdiffs[] = self::dif ($value,$mean);
        }
        $sqdiffsum = 0;
        // Square and sum the differences
        $sqmdiffs = 0;
        foreach ($mdiffs as $mdiff) {
            $sqmdiffs += pow ($mdiff, 2);
        }
        // Get variance (mean of sqdiffs)
        $variance = $sqmdiffs / (count ($values) - 1);
        // return the standard deviation
        return sqrt ($variance);
    }
}

class deg2Regression {
    
    public $regr = NULL;
    public $sqregr = NULL;
    
    
    // btw this doesn't work at all :-)
    function __construct ($x, $y) {
        // linear regression first
        $this->regr = new linearRegression ($x, $y);
        // now correct x for this
        $cr_l = $this->regr->getCorrection ();
        $xc_l = array_map (function ($x) use ($cr_l) { return $cr_l->correct ($x); }, $x);
        
        // Now square the corrected exes
        $xsq = array_map (function ($x) { return pow ($x, 2); }, $xc_l);
        // An do another linear regression on them
        $this->sqregr = new linearRegression ($xsq, $y);
        // Add the offsets (hrmm..!?)
        $offset = $this->regr->bfB() + $this->sqregr->bfB();
        //$xsqm = array_map (function ($x) use ($c) { return $x * $c; }, $xsq);
        $this->a = $this->sqregr->bfA (); // a * x ^ 2 +
        $this->b = $this->regr->bfA(); // b * x +
        $this->c = $offset; // + c = y
        
    }
    
    function getCorrection () {
        return new deg2Polynomial ($this->a, $this->b, $this->c);
    }
}

class linearRegression {
    
    protected $vals = array ();
    protected $x = array ();
    protected $y = array ();
    protected $mean_x = 0;
    protected $mean_y = 0;
    
    function getCorrection () {
        return new deg1Polynomial ($this->bfA (), $this->bfB ());
    }
    
    public function __construct ($arr_x, $y) {
        $this->loadData ($arr_x, $y);
    }
    public function loadData ($arr_x, $y) {
        $this->vals = array_combine ($arr_x, $y);
        //$this->x = $arr_x;
        //$this->y = $y;
        
        foreach ($this->vals as $x => $y) {
            if (is_numeric ($x) && is_numeric ($y)) {
                $this->x[] = $x;
                $this->y[] = $y;
            }
        }
        
        $this->mean_x = cal::mean ($this->x);
        $this->mean_y = cal::mean ($this->y);
        
    }
    
    // These get a and b values for a line of best fit (y= ax + b)
    public function bfB () { // intercept (b)
        return ($this->mean_y - ($this->bfA() * $this->mean_x));
    }
    public function bfA () { // slope (a)
        $topsum = 0;$botsum = 0;
        foreach ($this->vals as $x => $y) {
            $var_x = ($x - $this->mean_x);
            $var_y = ($y - $this->mean_y);
            $topsum += ($var_x * $var_y);
            $botsum += (pow ($var_x, 2));
        }
        return $topsum / $botsum;
    }
    
    // correlation coefficient (r)
    public function regR () {
        $topsum = 0; $botsum_x = 0; $botsum_y = 0;
        foreach ($this->vals as $x => $y) {
            $var_x = ($x - $this->mean_x);
            $var_y = ($y - $this->mean_y);
            $topsum += ($var_x * $var_y);
            $botsum_x += (pow ( $var_x, 2));
            $botsum_y += (pow ( $var_y, 2));
            $bot = sqrt ( ($botsum_x * $botsum_y) );
        }
        return $topsum / $bot;
    }
    // coefficient of determination (r^2)
    public function regRSq () {
        return pow ($this->regR (), 2);
    }
    public function regRSqPc () {
        return ($this->regRSq() * 100);
    }
    public function mean_x () { return $this->mean_x; }
    public function mean_y () { return $this->mean_y; }
    
    
}


















?>
