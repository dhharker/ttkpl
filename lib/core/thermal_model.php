<?php namespace ttkpl;
/*
* Copyright 2008-2011 David Harker
*
* Licensed under the EUPL, Version 1.1 or – as soon they
  will be approved by the European Commission - subsequent
  versions of the EUPL (the "Licence");
* You may not use this work except in compliance with the
  Licence.
* You may obtain a copy of the Licence at:
*
* http://ec.europa.eu/idabc/eupl
*
* Unless required by applicable law or agreed to in
  writing, software distributed under the Licence is
  distributed on an "AS IS" basis,
* WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either
  express or implied.
* See the Licence for the specific language governing
  permissions and limitations under the Licence.
*/

/**
 * @author David Harker david.harker@york.ac.uk
 */

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
        $offset %= ($this->period - 1);
        $pi = pi();
        return $this->Ta + $this->A0 * sin ((2 * $pi * ($offset - $this->minOffset)) / $this->period - (0.5*$pi));
    }
    /**
     * @todo refactor to use scalar everywhere that it's appropriate. 
     * @param int 1 <= offset <= sine::period
     * @return scalar 
     */
    public function getValueScalar ($offset) {
        return scalarFactory::makeKelvin($this->getValue ($offset));
    }

    function __toString () {
        return sprintf ("%0.1f±%0.1f°C",
                $this->Ta + scalarFactory::kelvinOffset,
                $this->A0);
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
                $nOff = $wksn->minOffset + ((($tl->z->getValue()*365)/self::dampingDepth ($tl->Dh))/(2*pi()));
                // or $nOff = $wksn->minOffset + ((($tl->z->getValue()*365)/self::dampingDepth ($tl->Dh)*2*pi()));
                
                $newAmp = clone $wksn->parentValues['amplitude'];
                $newAmp->desc = "Buffered amplitude";
                /* @TODO: This is a candidate to investigate as cause of the "too warm" bug */
                $newAmp->setScalar ($nAmp * 2);

                $newOff = clone $wksn->parentValues['minOffset'];
                $newOff->desc = "Buffered phase offset (lag)";
                $newOff->setScalar ($nOff);

                $newSine = clone $wksn;
                $newSine->parentValues['amplitude'] = array ($wksn->parentValues['amplitude'], $tl);
                $newSine->desc = "Thermally buffered temperature sine (layer " . $li+1 . ")";
                $newSine->setSine ($newSine->parentValues['mean'], $newAmp, $newOff);
                $wksn = $newSine;
                // DEBUG:
                // echo "intermediate ampl $li: " . $newAmp->getScalar()->getValue() / 2 . "\n";
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
class thermalLayer {

    public $z = NULL;
    public $Dh = NULL;
    public $desc = "Undescribed thermal layer.";

    public function __construct ($z, $Dh, $desc) {
        if (!is_object ($z))
            $z = scalarFactory::makeMetres ($z);
        if (!is_object ($Dh))
            $Dh = scalarFactory::makeThermalDiffusivity ($Dh);
        $this->z = $z;
        $this->Dh = $Dh;
        $this->desc = $desc;
    }
    function __toString () {
        $rdp = 3;
        return "(".round ($this->z->getValue(), $rdp)."".$this->z->unitsShort.",".round ($this->Dh->getValue(), $rdp).")";
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
        $vars = array (PMIP2::TMAX_VAR, PMIP2::TMIN_VAR, PMIP2::TMEAN_VAR);
        $models = array (PMIP2::MODEL_HADCM3M2, PMIP2::MODEL_CCSM);
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
            //$localOffs[$ptc] = $this->getLocalDayMinOffsetAt ($where, $ptc);
            $when = pmip::ptcToPalaeoTime ($ptc);
            // In degrees kelvin offset from northern hemisphere 40-80lat mean land surface temperature
            $globalTemps[$ptc] = $this->getGlobalMeanAnomalyAt ($when);
            $xyTemps['yT'][] = $localTemps[$ptc]->getScalar ()->getValue ();
            $xyTemps['yA'][] = $localAmps[$ptc]->getScalar ()->getValue ();
            $xyTemps['x'][] = $globalTemps[$ptc]->getScalar ()->getValue ();
            $offset = (isset ($offset)) ? $offset : $this->getLocalDayMinOffsetAt ($where, $ptc);
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
            'offset' => $offset
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
        
        // This is not cool at all but we need a bit or a redesign and this will have to do in its place for now:
        //$this->pmipIdx[$model][$pmipTimeConst][PMIP2::TMIN_VAR]->importer->_extractTemps($where->getLat(), $where->getLon(), PMIP2::TMEAN_VAR, $pmipTimeConst, PMIP2::MODEL_CCSM);

        //$min = $this->pmipIdx[$model][$pmipTimeConst][PMIP2::TMIN_VAR]->getInterpolatedValueFromFacet ($where);
        //$max = $this->pmipIdx[$model][$pmipTimeConst][PMIP2::TMAX_VAR]->getInterpolatedValueFromFacet ($where);
        
        $mean = $this->pmipIdx[PMIP2::MODEL_CCSM][$pmipTimeConst][PMIP2::TMEAN_VAR]->getInterpolatedValueFromFacet ($where);
        
        //$mean = datum::mean (array ($min, $max));
        return $mean;
    }
    function getLocalAmplitudeAt (facet $where, $pmipTimeConst = PMIP2::T_PRE_INDUSTRIAL_0KA, $model = PMIP2::MODEL_HADCM3M2) {
        $min = $this->pmipIdx[$model][$pmipTimeConst][PMIP2::TMIN_VAR]->getInterpolatedValueFromFacet ($where);
        $max = $this->pmipIdx[$model][$pmipTimeConst][PMIP2::TMAX_VAR]->getInterpolatedValueFromFacet ($where);

        $peakToPeakTemperatureAmplitude = datum::difference ($max, $min);
        return $peakToPeakTemperatureAmplitude;
    }
    
    
    // These are the original functions renamed from getLocalMeanTempAt and getLocalAmplitudeAt - using max of max and min of min may have been skewing model...
    function getExtremeLocalMeanTempAt (facet $where, $pmipTimeConst = PMIP2::T_PRE_INDUSTRIAL_0KA, $model = PMIP2::MODEL_HADCM3M2) {
        $min = $this->pmipIdx[$model][$pmipTimeConst][PMIP2::TMIN_VAR]->getInterpolatedValueFromFacet ($where);
        $max = $this->pmipIdx[$model][$pmipTimeConst][PMIP2::TMAX_VAR]->getInterpolatedValueFromFacet ($where);

        $mean = datum::mean (array ($min, $max));
        return $mean;
    }
    function getExtremeLocalAmplitudeAt (facet $where, $pmipTimeConst = PMIP2::T_PRE_INDUSTRIAL_0KA, $model = PMIP2::MODEL_HADCM3M2) {
        $min = $this->pmipIdx[$model][$pmipTimeConst][PMIP2::TMIN_VAR]->getInterpolatedValueFromFacet ($where);
        $max = $this->pmipIdx[$model][$pmipTimeConst][PMIP2::TMAX_VAR]->getInterpolatedValueFromFacet ($where);

        $peakToPeakTemperatureAmplitude = datum::difference ($max, $min);
        return $peakToPeakTemperatureAmplitude;
    }

    function getLocalDayMinOffsetAt (facet $where, $pmipTimeConst = PMIP2::T_PRE_INDUSTRIAL_0KA, $model = PMIP2::MODEL_HADCM3M2) {
        $dmin = $this->pmipIdx[$model][$pmipTimeConst][PMIP2::TMIN_VAR]->getDayMinOffsetFromFacet ($where);
        //var_dump ($dmin); die("*");
        return $dmin;
    }

    function getLocalElevationAt (facet $where, $pmipTimeConst = PMIP2::T_PRE_INDUSTRIAL_0KA, $model = PMIP2::MODEL_HADCM3M2) {
        $elev = $this->pmipIdx[$model][$pmipTimeConst][PMIP2::ALT_VAR]->getElevationFromFacet ($where);
        //var_dump ($dmin); die("*");
        return $elev;
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

    public $corrections = array (); // array of correction objects which get sequentially applied
    public $location = null; // latLon object locating site. currently for reference in reporting only.
    public $temperatures; // object with data access and stuff
    public $date; // contains the palaeotime from which interped sine values are pulled
    public $wsSine; // working start sine (e.g. global temps)
    public $intermediateSines = array (); // contains sines as each correction is applied

    private $controllingThermalAge = null; // @TODO this properly: ref to thermalAge object pulling the strings to use getTeffFrom(histogram|sine) fns.
    
    function __construct () {

    }
    function setControllingThermalAge (\ttkpl\thermalAge $ta) {
        $this->controllingThermalAge = $ta;
    }

    function timeWalk ($numBins = -1, $xText = 0) {
        
        $graphTeff = true; // may cause significant performance hit

        $bpStart = $this->startDate->getYearsBp ();
        $bpStop = $this->stopDate->getYearsBp ();
        $tHist = new histogram ();
        $this->twData = array (
            'mean' => array (),
            'amp' => array (),
        );
        if ($graphTeff && $this->controllingThermalAge !== null) {
            $this->twData['teff'] = array ();
        }
        elseif ($graphTeff) {
            $graphTeff = false;
        }
        if (isset ($this->intermediateSines[1])) {
            $this->twData['TGraph'] = array ();
        }
        
        log_message ('debug', " * timeWalk: doing the timewalk:");

        $prevTime = microtime(true);
        $prevSplTime = 0.0;
        $spls = array ();
        $debugClock = 0;

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
            }

            if ($graphTeff && $this->controllingThermalAge !== null) {
                $tmpTeff = $this->controllingThermalAge->getTeffFromSine($this->wsSine);
                $tC = $tmpTeff->getValue()  + scalarFactory::kelvinOffset;
                // DEBUG:
                // log_message ('debug', "Teff @ $years is $tC");
                $this->twData['teff'][$years] = $tC;
            }

            $this->twData['mean'][$years] = $this->wsSine->mean + scalarFactory::kelvinOffset;
            $this->twData['amp'][$years] = $this->wsSine->amplitude;

            if (isset ($this->gtc))
                $this->twData['ganom'][$years] = $this->gtc->getValue();


            $endTime = microtime(true);
            $lastTime = $endTime - $prevTime;
            $spls[] = $lastTime;
            $prevTime = $endTime;

            if ($debugClock == 0) {
                $cnumSpls = count ($spls);
                $avgSps = (array_sum ($spls) / $cnumSpls);
                $avgSpSp = 1 / $avgSps;
                $SpSp = 1 / $lastTime;
                log_message ('debug', sprintf ("Done %d samples, avg. speed: %01.5f sec/spl (%01.3f/sec), last sample: %01.5f sec (%01.3f/sec)", $cnumSpls, $avgSps, $avgSpSp, $lastTime, $SpSp) );
            }
            $debugClock = ++$debugClock % 10;
            
        }
        
        $tHist->setBins ($numBins);
        $tHist->getBinCounts ($xText);
        
        $this->twData['sec_spl_yr'] = array_sum ($spls) / count ($spls);

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
    function getSineFromGlobal (scalar $ganom) {
        if ($this->setSineFromGlobal($ganom) !== FALSE) {
            return $this->wsSine;
        }
        return FALSE;
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
            $newSine->setGenericSine ($lm, $la, $this->initDayMinOffset);
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
        return true;
    }
    function setTempSource (temperatures $t) {
        $this->temperatures = $t;
    }
    function setChunkSize ($chYears) {
        $this->chunkSize = $chYears;
    }
    function autoChunkSize ($l = 5, $u = 25000) {
        //return 1;
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
        $this->initDayMinOffset = (isset ($arrLC['offset'])) ? $arrLC['offset']->getValue()->getValue() : die();
        
    }
    function setLocation (\ttkpl\latLon $location) {
        $this->location = $location;
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
        $this->autoChunkSize();
        //echo (print_r (array ($this->startDate, $this->stopDate), TRUE));
    }

    function getWsSine () {
        return $this->wsSine;
    }

    // bintanja
    // g>l temp correction
    // g>a temp correction
    // vegetation cover correction
    // burial layer corrections
    // start date
    // end date

}

