<?php

interface scalarInterface {
    public function setValue ($value);
    public function getValue ();
    public function getUnitsLong ();
    public function getUnitsShort ();
    public function validateValue ($value = NULL);
}


class scalar implements scalarInterface {
    public $conversions = array (); // of functions to convert from other things to this thing
    public $intName = "ARBITRARY_UNITS"; // keys for conversions array
    public $value = 0;
    public $unitsLong = 'Arbitrary Units';
    public $unitsShort = 'AU';
    public $validationFunction = NULL;
    public $desc = 'Generic scalar variable.'; // normally should describe the data
    public $dataSetObject = NULL;
    
    public function __construct (dataSet &$dso = NULL) {
        $this->dataSetObject = &$dso;
        $this->validationFunction = function ($v) { return TRUE; };
    }
    public function setValue ($value) {
        
        if (is_object ($value) && in_array ($this->intName, array_keys ($value->conversions))) {
            $cf = $value->conversions[$this->intName];
            $value = $cf ($value->getValue());
        }
        
        return ($this->validateValue ($value) == TRUE) ? $this->value = $value : FALSE;
    }
    public function getValue () {
        return $this->value;
    }
    
    public function validateValue ($value = NULL) {
        if ($value === NULL)
            $value = $this->getValue ();
        $vf = $this->validationFunction;
        return $vf ($value);
    }
    public function getUnitsLong () {
        return $this->unitsLong;
    }
    public function getUnitsShort () {
        return $this->unitsShort;
    }
    public function getDescription () {
        return $this->desc;
    }
    public function getDataSetDescription () {
        return (is_object ($this->dataSetObject)) ? $this->dataSetObject->getDataSetDescription () : FALSE;
    }
    
    // for compatibility with datum
    public function getScalar () {
        return $this;
    }
}

class scalarFactory {
    const kelvinOffset = -273.15;
    const yearsWBp = 1950;
    const yearLengthDays = 365;
    
    static function _getNowBp () {
        return scalarFactory::yearsWBp - (date ('Y') + 0.0);
    }
    static function _getAdBp ($yearsAd) {
        return scalarFactory::yearsWBp - $yearsAd;
    }
    static function secsPerYear () {
        return scalarFactory::yearLengthDays * scalarFactory::secsPerDay();
    }
    static function secsPerDay () {
        return 24  * 60 * 60;
    }
    static function makeYearsBp ($value = "NOW", dataSet &$dataSet = NULL) {
        if ($value == "NOW")
            $value = scalarFactory::_getNowBp ();
        $s = new scalar ();
        $s->intName = "YEARS_BEFORE_" . scalarFactory::yearsWBp;
        $s->unitsLong = "Years (of " . scalarFactory::yearLengthDays . " days) before present (" . scalarFactory::yearsWBp . ")";
        $s->unitsShort = "yrs. b.p.";
        $s->validationFunction = function ($v) {
            $min = scalarFactory::yearsWBp - (date ('Y') + 0.0);
            return (is_numeric ($v) && $v >= $min) ? TRUE : FALSE;
        };
        /*$s->conversions['YEARS_'] = function ($c) {
            return $c + scalarFactory::kelvinOffset;
        };*/
        $s->setValue ($value);
        $s->dataSetObject = &$dataSet;
        return $s;
    }
    static function makeKelvin ($value, dataSet &$dataSet = NULL) {
        $s = new scalar ();
        $s->intName = "DEG_K_ABS";
        $s->unitsLong = "Degrees Kelvin";
        $s->unitsShort = "K";
        $s->validationFunction = function ($v) {
            return (is_numeric ($v) && $v >= 0) ? TRUE : FALSE;
        };
        $s->conversions['DEG_C_ABS'] = function ($c) {
            return $c + scalarFactory::kelvinOffset;
        };
        $s->setValue ($value);
        $s->dataSetObject = &$dataSet;
        return $s;
    }
    static function makeKelvinAnomaly ($value, dataSet &$dataSet = NULL) {
        $s = new scalar ();
        $s->intName = "DEG_K_ANOM";
        $s->unitsLong = "Degrees Kelvin Anomaly";
        $s->unitsShort = "K";
        $s->validationFunction = function ($v) {
            return (is_numeric ($v)) ? TRUE : FALSE;
        };
        $s->conversions['DEG_C_ANOM'] = function ($c) {
            return $c;
        };
        $s->setValue ($value);
        $s->dataSetObject = &$dataSet;
        return $s;
    }
    static function makeCentigradeAbs ($value, dataSet &$dataSet = NULL) {
        $s = new scalar ();
        $s->intName = "DEG_C_ABS";
        $s->unitsLong = "Degrees Centigrade";
        $s->unitsShort = "C";
        $s->validationFunction = function ($v) {
            return (is_numeric ($v) && $v >= scalarFactory::kelvinOffset) ? TRUE : FALSE;
        };
        $s->conversions['DEG_K_ABS'] = function ($k) {
            return $k - scalarFactory::kelvinOffset;
        };
        $s->setValue ($value);
        $s->dataSetObject = &$dataSet;
        return $s;
    }
    static function makeKilometres ($value, dataSet &$dataSet = NULL) {
        $s = new scalar ();
        $s->intName = "DIST_KM";
        $s->unitsLong = "Kilometres";
        $s->unitsShort = "km";
        $s->validationFunction = function ($v) {
            return (is_numeric ($v)) ? TRUE : FALSE;
        };
        $s->conversions['DIST_M'] = function ($m) {
            return $m / 1000;
        };
        $s->setValue ($value);
        $s->dataSetObject = &$dataSet;
        return $s;
    }
    static function makeMetres ($value, dataSet &$dataSet = NULL) {
        $s = new scalar ();
        $s->intName = "DIST_M";
        $s->unitsLong = "Metres";
        $s->unitsShort = "m";
        $s->validationFunction = function ($v) {
            return (is_numeric ($v)) ? TRUE : FALSE;
        };
        $s->conversions['DIST_KM'] = function ($m) {
            return $m * 1000;
        };
        $s->setValue ($value);
        $s->dataSetObject = &$dataSet;
        return $s;
    }
    static function makeMolesPerYear ($value, dataSet &$dataSet = NULL) {
        $s = new scalar ();
        $s->intName = "MOL_YEAR";
        $s->unitsLong = "Moles per Year";
        $s->unitsShort = "mol. yr.^-1";
        $s->validationFunction = function ($v) {
            return (is_numeric ($v) && $v > 0) ? TRUE : FALSE;
        };
        $s->conversions['MOL_SEC'] = function ($m) {
            return $m / scalarFactory::secsPerYear();
        };
        $s->setValue ($value);
        $s->dataSetObject = &$dataSet;
        return $s;
    }
    static function makeMolesPerSecond ($value, dataSet &$dataSet = NULL) {
        $s = new scalar ();
        $s->intName = "MOL_YEAR";
        $s->unitsLong = "Moles per Second";
        $s->unitsShort = "mol. sec.^-1";
        $s->validationFunction = function ($v) {
            return (is_numeric ($v) && $v > 0) ? TRUE : FALSE;
        };
        $s->conversions['MOL_SEC'] = function ($m) {
            return $m * scalarFactory::secsPerYear();
        };
        $s->setValue ($value);
        $s->dataSetObject = &$dataSet;
        return $s;
    }
    static function makeJoulesPerMole ($value, dataSet &$dataSet = NULL) {
        $s = new scalar ();
        $s->intName = "J_MOL";
        $s->unitsLong = "Joules per Mole";
        $s->unitsShort = "J mol.^-1";
        $s->validationFunction = function ($v) {
            return (is_numeric ($v) && $v > 0) ? TRUE : FALSE;
        };
        $s->conversions['KJ_MOL'] = function ($m) {
            return $m / 1000;
        };
        $s->setValue ($value);
        $s->dataSetObject = &$dataSet;
        return $s;
    }
    static function makeKilojoulesPerMole ($value, dataSet &$dataSet = NULL) {
        $s = new scalar ();
        $s->intName = "KJ_MOL";
        $s->unitsLong = "Kilojoules per Mole";
        $s->unitsShort = "kJ mol.^-1";
        $s->validationFunction = function ($v) {
            return (is_numeric ($v) && $v > 0) ? TRUE : FALSE;
        };
        $s->conversions['J_MOL'] = function ($m) {
            return $m * 1000;
        };
        $s->setValue ($value);
        $s->dataSetObject = &$dataSet;
        return $s;
    }
    static function makeAU ($value, dataSet &$dataSet = NULL) {
        $s = new scalar ();
        $s->intName = "AU";
        $s->unitsLong = "Arbitrary Units";
        $s->unitsShort = "A.U.";
        $s->validationFunction = function ($v) {
            return TRUE;
        };
        $s->setValue ($value);
        $s->dataSetObject = &$dataSet;
        return $s;
    }
    static function makeThermalDiffusivity ($value, dataSet &$dataSet = NULL) {
        $s = new scalar ();
        $s->intName = "Dh";
        $s->unitsLong = "Meters squared per second";
        $s->unitsShort = "m^2 s^-1";
        $s->validationFunction = function ($v) {
            return (is_numeric ($v) == TRUE && $v > 0.0) ? TRUE : FALSE;
        };
        $s->setValue ($value);
        $s->dataSetObject = &$dataSet;
        return $s;
    }
    static function makeDays ($value, dataSet &$dataSet = NULL) {
        $s = new scalar ();
        $s->intName = "DAYS";
        $s->unitsLong = "Days";
        $s->unitsShort = "days";
        $s->validationFunction = function ($v) {
            return (is_numeric ($v) == TRUE) ? TRUE : FALSE;
        };
        $s->conversions['YEARS'] = function ($m) {
            return $m / scalarFactory::yearLengthDays;
        };
        $s->conversions['YEARS'] = function ($m) {
            return $m * scalarFactory::secsPerYear();
        };
        $s->setValue ($value);
        $s->dataSetObject = &$dataSet;
        return $s;
    }
    static function makeYears ($value, dataSet &$dataSet = NULL) {
        $s = new scalar ();
        $s->intName = "YEARS";
        $s->unitsLong = "Years";
        $s->unitsShort = "yrs.";
        $s->validationFunction = function ($v) {
            return (is_numeric ($v) == TRUE) ? TRUE : FALSE;
        };
        $s->conversions['SECONDS'] = function ($m) {
            return $m * scalarFactory::secsPerYear();
        };
        $s->conversions['DAYS'] = function ($m) {
            return $m * scalarFactory::yearLengthDays;
        };
        $s->setValue ($value);
        $s->dataSetObject = &$dataSet;
        return $s;
    }
    static function makeSeconds ($value, dataSet &$dataSet = NULL) {
        $s = new scalar ();
        $s->intName = "SECONDS";
        $s->unitsLong = "Seconds";
        $s->unitsShort = "sec.";
        $s->validationFunction = function ($v) {
            return (is_numeric ($v) == TRUE) ? TRUE : FALSE;
        };
        $s->conversions['YEARS'] = function ($m) {
            return $m / scalarFactory::secsPerYear();
        };
        $s->conversions['DAYS'] = function ($m) {
            return $m / scalarFactory::secsPerDay();
        };
        $s->setValue ($value);
        $s->dataSetObject = &$dataSet;
        return $s;
    }
    
    static function make10CThermalYears ($value, dataSet &$dataSet = NULL) {
        $s = new scalar ();
        $s->intName = "10C_THERMAL_YEARS";
        $s->unitsLong = "10C Thermal Years";
        $s->unitsShort = "10C yrs.";
        $s->validationFunction = function ($v) {
            return (is_numeric ($v) == TRUE) ? TRUE : FALSE;
        };
        $s->conversions['10C_THERMAL_SECONDS'] = function ($m) {
            return $m * scalarFactory::secsPerYear();
        };
        $s->setValue ($value);
        $s->dataSetObject = &$dataSet;
        return $s;
    }
    static function make10CThermalSeconds ($value, dataSet &$dataSet = NULL) {
        $s = new scalar ();
        $s->intName = "10C_THERMAL_SECONDS";
        $s->unitsLong = "10C Thermal Seconds";
        $s->unitsShort = "10C sec.";
        $s->validationFunction = function ($v) {
            return (is_numeric ($v) == TRUE) ? TRUE : FALSE;
        };
        $s->conversions['10C_THERMAL_YEARS'] = function ($m) {
            return $m / scalarFactory::secsPerYear();
        };
        $s->setValue ($value);
        $s->dataSetObject = &$dataSet;
        return $s;
    }
}

interface facetInterface {
    public static function _bootstrap ($arrArg);
    public function distanceTo (facet $to);
}

abstract class facet implements facetInterface {
    function _wrap ($value, $modulo, $offset = NULL) {
        if ($offset === NULL)
            $offset = $modulo / 2;
        $value += $offset;
        $value %= $modulo;
        $value -= $offset;
        return $value;
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

class kinetics {
    
    public $Ea = NULL;
    public $F = NULL;
    public $desc = "Undescribed kinetic parameters!";
    
    const GAS_CONSTANT = 8.314472;
    
    
    public function __construct ($Ea, $F, $desc) {
        $this->Ea = scalarFactory::makeKilojoulesPerMole ($Ea);
        $this->Ea->desc = "Energy of activation";
        $this->F = scalarFactory::makeSeconds ($F);
        $this->F->desc = "Pre-exponential factor";
        $this->desc = $desc;
    }
    public function getRate (scalar $T) {
        if ($T->intName == 'DEG_K_ABS')
            $Tkelvin = $T->getValue ();
        elseif ($T->intName == 'DEG_C_ABS') {
            $ks = scalarFactory::makeKelvin ($T);
            //print_r ($ks);
            $Tkelvin = $ks->getValue ();
            //print_r ($T);
        }
        else
            return FALSE;
            
        $RoR = $this->F->getValue() * exp ((-$this->Ea->getValue())/(self::GAS_CONSTANT * $Tkelvin));
        return scalarFactory::makeMolesPerSecond ($RoR); // i.e. k_T
    }
    public function getTempAtRate (scalar $k) {
        $Tkelvin = (-$this->Ea->getValue()/self::GAS_CONSTANT) / (log ($k->getValue()) - log ($this->F->getValue()));
        return scalarFactory::makeKelvin ($Tkelvin);
    }

}

class histogram {
    
    public $x = array ();
    public $numBins = -1;
    public $rehash = TRUE;
    public $binWidth = 1;
    public $range = 0;
    public $bins = array ();     // labels and bins have indexen in synch.
    public $labels = array ();  //
    const roundDp = 6;
    
    public function addPoint ($x) {
        $x = (array) $x;
        $this->x = array_values ($x);
        $this->rehash = TRUE;
    }
    public function setBins ($numBins, $uBound = 500, $lBound = 250) {
        if ($numBins == -1 || !(is_numeric ($numBins) && $numBins > 0)) {
            $p = $this->countPoints ();
            $n = 2 * sqrt($p);
            $n = round ($n);
            $n = ($n < $lBound) ? $lBound : $n;
            $n = ($n > $uBound) ? $uBound : $n;
            $this->numBins = $n;
        }
        elseif (is_numeric ($numBins) && $numBins > 0)
            $this->numBins = $numBins;
        else
            $this->numBins = 100;
        $this->rehash = TRUE;
    }
    public function getBinCounts ($xText = 0) {
        switch ($xText) {
            default:
            case 0: // midpoint
                $tf = function ($min, $max) {
                    return round (cal::mean ($min, $max), histogram::roundDp). '';
                };
                break;
            case 1: // range
                $tf = function ($min, $max) {
                    return round ($min, histogram::roundDp) . " - " . round ($max, histogram::roundDp);
                };
                break;
            case 2: // range (useful even?)
                $tf = function ($min, $max) {
                    return round ($max - $min, histogram::roundDp) . '';
                };
                break;
            
        }
        
        if ($this->rehash == TRUE) {
            $this->setBins ($this->numBins);
            $this->bins = array ();
            $this->max = max ($this->x);
            $this->min = min ($this->x);
            $this->range = $this->max - $this->min;
            $this->numPoints = count ($this->x);
            $this->binWidth = (($this->range > 0) ? $this->range : 1E-9) / ($this->numBins - 1);
            $this->rehash = FALSE;
        }
        
        for ($bin = 0; $bin < $this->numBins; $bin++) {
            $min = $bin * $this->binWidth + $this->min;
            $max = ($bin + .9999999999999999) * $this->binWidth + $this->min;
            $this->labels[$bin] = ($tf ($min, $max));
            $this->bins[$bin] = 0;
        }
        
        foreach ($this->x as $xi => $x) {
            $bin = round (($x - $this->min) / $this->binWidth);
            $this->bins[$bin]++;
        }
    }
    function countPoints () {
        return count ($this->x);
    }
    public function getBinWidth () {
        return $this->binWidth;
    }
    
    
}
class dnaHistogram extends histogram {
    
    function numBonds () {
        $this->numBonds = 0;
        $this->bondsInBin = array();
        foreach ($this->bins as $numBonds => $numFragments) {
            $this->bondsInBin[$numBonds] = $numBonds * $numFragments;
            $this->numBonds += $this->bondsInBin[$numBonds];
        }
        return $this->numBonds;
    }
    
}

class latLon extends facet {
    
    public $lat = 0;
    function setLat ($lat) {
        return ($this->_isLat ($lat)) ? ($this->lat = $lat + 0.0) : FALSE;
    }
    function getLat () {
        return $this->lat;
    }
    
    public $lon = 0;
    function setLon ($lon) {
        return ($this->_isLon ($lon)) ? ($this->lon = $lon + 0.0) : FALSE;
    }
    function getLon () {
        return $this->lon;
    }
    
    function __toString () {
        $rdp = 3;
        $a = round ($this->lat, $rdp);
        $o = round ($this->lon, $rdp);
        return "{$a}N,{$o}E";
    }
    
    public function distanceTo (facet $to) {
        return (get_class ($to) == 'latLon') ? self::haversine ($this, $to) : FALSE;
    }
    
    
    function __construct ($latA = NULL, $lonA = NULL) {
        return $this->setLatLon ($latA, $lonA);
    }
    
    // internal
    static function haversine (latLon $from, latLon $to) {
        // adapted from http://www.movable-type.co.uk/scripts/latlong.html
        $R = 6371; // km mean earth radius (ellipsoidal model reduces error by up to .3% or something but who has time...)
        //$R = 6378.160; // from http://www.sunearthtools.com/dp/tools/pos_earth.php#accuracy
        $dLat = deg2rad ($to->getLat () - $from->getLat ());
        $dLon = deg2rad ($to->getLon () - $from->getLon ()); 
        $a = sin($dLat/2) * sin($dLat/2) +
                cos (deg2rad ($from->getLat ())) * cos (deg2rad ($from->getLat ())) * 
                sin($dLon/2) * sin($dLon/2); 
        $c = 2 * atan2(sqrt($a), sqrt(1-$a)); 
        $dkm = $R * $c;
        return scalarFactory::makeKilometres ($dkm);
    }
    static function _bootstrap ($arrArg) {
        $o = new latLon ();
        if (is_object ($arrArg) && get_class ($arrArg) == 'latLon')
            return $arrArg;
        return @(($o->setLatLon ($arrArg['lat'], $arrArg['lon'])) || ($o->setLatLon ($arrArg[0], $arrArg[1])) || ($o->setLatLon ($arrArg[1], $arrArg[0]))) ? $o : FALSE;
    }
    function _isLat ($lat) {
        return (is_numeric ($lat) && $lat <= 90 && $lat >= -90) ? TRUE : FALSE;
    }
    function _isLon ($lon) {
        return (is_numeric ($lon) && $lon <= 180 && $lon >= -180) ? TRUE : FALSE;
    }
    function _wrapLat ($lat) {
        return $this->_wrap ($lat, 180);
    }
    function _wrapLon ($lon) {
        return $this->_wrap ($lon, 360);
    }
    
    
    // convenience
    function setLatLon ($lat, $lon) {
        return $this->setLat ($lat) && $this->setLon ($lon);
    }
    
}

class palaeoTime extends facet {

    
    public $timeScalar = NULL;
    
    function getYearsBp () {
        return $this->timeScalar->getValue ();
    }
    function setYearsBp ($ybp) {
        $this->init ($ybp);
        return $this->timeScalar->setValue ($ybp);
    }
    
    public function distanceTo (facet $to) {
        return (get_class ($to) == 'palaeoTime') ? $to->getYearsBp () - $this->getYearsBp () : FALSE;
    }
    
    public function init ($yearsBp = NULL, dataSet &$ds = NULL) {
        if (!is_object ($this->timeScalar) || !is_a ($this->timeScalar, 'scalar'))
            $this->timeScalar = scalarFactory::makeYearsBp ($yearsBp, $ds);
        elseif (is_object ($yearsBp) && get_class ($yearsBp) == 'scalar')
            $this->timeScalar = clone ($yearsBp);
        else
            return FALSE;
        return TRUE;
    }
    public function __construct ($yearsBp = NULL, dataSet &$ds = NULL) {
        return $this->init ($yearsBp, $ds);
    }
    
    //internal
    static function _bootstrap ($arrArg) {
        if (is_object ($arrArg) && is_a ($arrArg, 'palaeoTime'))
            return $arrArg;
        elseif (is_numeric ($arrArg))
            $v = $arrArg;
        elseif (is_array ($arrArg) && isset ($arrArg['ybp']) && is_numeric ($arrArg['ybp']))
            $v = $arrArg['ybp'];
        else
            return FALSE;
        return new palaeoTime ($v);
        
    }
}




abstract class taUtils {
    function __construct () {
        //parent::__construct ();
        
    }
    
    function invWeightedMean ($values, $invWeights) {
        //print_r (array ($values, $invWeights));
//         $get = function (&$v) {
//             print_r ($v);
//             if (!is_object ($v))
//                 return $v;
//             switch (get_class ($v)) {
//                 case "datum":
//                 case "temporalDatum":
//                 case "spatialDatum":
//                     return $v->getScalar()->getValue();
//                     break;
//                 case "scalar":
//                     return $v->getValue();
//                     break;
//             }
//         };
//         
//         $values = array_map ($get, $dvalues);
//         $invWeights = array_map ($get, $dinvWeights);
//         print_r ($values);
        $sum = 0;
        $iws = array_sum ($invWeights);
        
        if ($iws == 0) {
            return cal::mean ($values);
            die (print_r ($invWeights, TRUE));
        }
        $ws = 0;
        foreach ($values as $vi => $vv) {
            $w = (1 / ($invWeights[$vi] / $iws));
            $sum += $vv * $w;
            $ws += $w;
        }
        return $sum / $ws;
    }
    
    static function filenameFromCrap ($str) {
        return preg_replace (array ('/[^a-z0-9_-]/i', '/(([_-])\2+)/'), array ('_', '_'), strtolower($str));
    }
    
}


interface dataSetInterface {
    public function getNearestRealFacets (facet $facet);
    public function isRealFacet (facet $facet);
    public function getRealValueFromFacet (facet $facet);
    public function getInterpolatedValueFromFacet (facet $facet);
    public function getPalaeoTime ();
    public static function getBlankScalar ();
}

abstract class dataSet extends taUtils implements dataSetInterface {
    
    function getDataSetDescription () {
        return "Abstract dataset wrapper class.";
    }
    
    public static function getBlankScalar ($a = NULL, $b = NULL) {
        // once we're using  stuff other than temperature with this, will need
        // to implement in each class and use this here:
        //return new scalar ($a, $b);
        // until then use AUs because could be abs/rel deg C/K:
        return scalarFactory::makeAU ($a, $b);
    }
    
    function getInterpolatedValueFromFacet (facet $facet) {
        
        if ($this->isRealFacet ($facet))
            return $this->getRealValueFromFacet ($facet);
        
        $nearFacets = $this->getNearestRealFacets ($facet);
        $values = array ();
        $weights = array ();
        foreach ($nearFacets as $fi => $nf) {
            $values[$fi] = $this->getRealValueFromFacet($nf)->getScalar()->getValue();
            $weights[$fi] = $facet->distanceTo($nf)->getValue();
            //$weights[$fi] = $weights[$fi];
        }
        
        $wm = $this->invWeightedMean ($values, $weights);
        $sc = self::getBlankScalar ($wm, $this);
        return $sc;
        
    }
    
}
/*
abstract class flatFile extends dataset {
    function __construct () {
        parent::__construct ();
        
    }
}*/

class datum extends taUtils {
    
    // Contains either a numeric value or a dataset object or a single datum object
    public $value = NULL;
    
    // Contains the data from which this datum is derived e.g. max and min temperature data in a mean datum
    public $parentValues = array ();
    
    
    function setValue (&$value) {
        if (is_object ($value) && get_class ($value) == __CLASS__)
            foreach (get_object_vars ($value) as $v => $val)
                $this->$v = $val;
        else
            $this->value = $value;
    }
    function getValue () {
        return $this->value;
    }
    
    // Does this container wrap another dataset (e.g. a temporalDatum containing spatialData)?
    public $isContainer = FALSE;
    
    // Is the value of this datum interpolated from other data?
    public $isInterpolated = FALSE;
    
    function getScalar ($in = NULL) {
        if ($in === NULL) {
            $in = $this->getValue ();
        }
        if (is_object ($in) && get_class ($in) == 'scalar')
            return $in;
        elseif ((is_object ($in) && isset ($in->value)))
            return $this->getScalar ($in->value);
//         elseif (is_numeric ($in))
//             return $in;
        else
            return FALSE;
    }
    function setScalar ($sc) {
        $csc = $this->getScalar ();
        $csc->setValue ($sc);
    }
    
    
    static function mean ($arrData) {
    //print_r ($arrData);
        $sum = 0;
        $count = 0;
        $s = null;
        foreach ((array) $arrData as $datum) {
            if (is_object ($datum) && get_class ($datum) == 'scalar')
                $s = $datum;
            elseif (is_object ($datum))
                $s = $datum->getScalar ();
            /*else
                if (is_numeric ($datum))
                    $s = $datum;
                else*/
            
            $v = $s->getValue ();
            
            $count++;
            $sum += $v;
            
        }
        if ($count > 0) {
            $mean = $sum / $count;
            $newScalar = clone ($s); // just use last scalar, they should all have same type anyway
            $newScalar->setValue ($mean);
            $newScalar->parentValues = $arrData;
            $newScalar->desc = "Mean value of parent values";
            return $newScalar;
        }
        else {
            return FALSE;
        }
    }
    static function difference ($max, $min) {
        $max = (get_class ($max) == 'scalar') ? $max : $max->getScalar ();
        $min = (get_class ($min) == 'scalar') ? $min : $min->getScalar ();
        $diff = $max->getValue () - $min->getValue ();
        $newScalar = clone ($min); 
        $newScalar->setValue ($diff);
        $newScalar->parentValues = array ($max, $min);
        $newScalar->desc = "Difference of parent values";
        return $newScalar;
    }
    
}

class temporalDatum extends datum {
    
    // palaeoTime object when this is
    public $palaeoTime = NULL;
    
    function __construct ($palaeoTime = NULL, $value = NULL) {
        $this->palaeoTime = palaeoTime::_bootstrap ($palaeoTime);
        $this->setValue ($value);
        
    }

}

class spatialDatum extends datum {
    
    // latLon object where this is
    public $latLon = NULL;
    
    function __construct ($latLon = NULL, $value = NULL) {
        $this->latLon = latLon::_bootstrap ($latLon);
        $this->setValue ($value);
    }
}




?>