<?php

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
 * Contains interfaces and classes for representing and operating on data types
 * @author David Harker david.harker@york.ac.uk
 */

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

        if (is_a ($value, 'scalar') && (in_array ($this->intName, array_keys ($value->conversions)))) {
            $cf = $value->conversions[$this->intName];
            $value = $cf ($value->getValue());
        }
        elseif (is_a ($value, 'scalar') && ($this->intName == $value->intName)) {
            // messed /up/! =)
            $value = $value->value;
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
        return self::_getAdBp(date ('Y')) + 0.00;
    }
    static function _getAdBp ($yearsAd) {
        return ($yearsAd - scalarFactory::yearsWBp) * -1.00;
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
            $min = scalarFactory::_getNowBp ();
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





?>
