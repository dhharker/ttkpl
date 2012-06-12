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


class thermalAge {

    public $temporothermals = array ();
    public $refTempC = 10;
    public $kinetics;
    public $rehash = TRUE;

    public $histograms = array ();
    public $rates = array ();

    public function addTemporothermal (temporothermal $tt) {
        $this->temporothermals[] = $tt;
        $this->rehash = TRUE;
    }
    public function setKinetics (kinetics $k) {
        $this->kinetics = $k;
        $this->rehash = TRUE;
    }
    /**
     * @todo this should probably return a scalar, though there's plenty of refactoring to be done for that yet
     * @return int years bp
     */
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
        $sumR = 0; $sumD = 0;
        for ($d = 1; $d <= $s->period; $d++) {
            $sumR += $this->getRate ($s->getValueScalar ($d))->getValue();
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
