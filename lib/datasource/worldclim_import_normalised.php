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

require_once ("bil_import.php");

class worldclim extends dataSet {
    public $importer = NULL;

    // These contain values of constants from the base PMIP2 importer class
    public $varname;
    public $timename;
    public $modelname;

    //const T_LGM_21KA = "21k";
    //const T_MID_HOLOCENE_6KA = "6k";
    const T_PRE_INDUSTRIAL_0KA = "0ka";
    const TMIN_VAR = 'tmin';
    const TMAX_VAR = 'tmax';
    const TMEAN_VAR = 'NOT AVAILABLE BUT MUST BE DEFINED';
    const ALT_VAR = 'alt';
    const RES_5M = '5m';

    function __construct ($varname = NULL, $timename = NULL, $resolution = NULL) {
        if ($varname === NULL)
            $varname = self::TMIN_VAR;
        if ($timename === NULL)
            $timename = self::T_PRE_INDUSTRIAL_0KA;
        if ($resolution === NULL)
            $resolution = self::RES_5M;

        $this->varname = $varname;
        $this->timename = $timename;
        $this->resolution = $resolution;

        $this->importer = new bil_import ("worldclim", self::RES_5M);
        $this->importer->loadDB ($varname, $timename, $resolution);
        
        //print_r ($this->importer->read (53,-1));
    }

    function _isVarSeasonal ($var) {
        return ($var == self::ALT_VAR) ? FALSE : TRUE;
    }

    function isRealFacet (facet $facet) {
        if (!$this->importer->_isRealLat($facet->getLat ())) {
            return false;
        }
        elseif (!$this->importer->_isRealLon($facet->getLon ())) {
            return false;
        }
        return true;
    }
    
    public function getNearestRealFacets (facet $facet) {
        $ar = $this->importer->nearestLatLons($facet->getLat(), $facet->getLon());
        $o = array ();
        foreach ($ar as $lla) {
            $o[] = new \ttkpl\latLon($lla['lat'], $lla['lon']);
        }
        return $o;
    }


    public function getRealValueFromFacet (facet $facet) {
        if (!$this->isRealFacet($facet)) return false;
        $temps = $this->importer->read ($facet->getLat (), $facet->getLon ());
        //debug ($temps);
        $scr = scalarFactory::makeKelvin ($this->importer->_getMaxMinMeanByVarName ($temps, $this->varname), $this);
        $td = new temporalDatum ($this->getPalaeoTime (), $scr);
        $sd = new spatialDatum ($facet, $td);
        return $sd;
    }
    public function getRealElevationFromFacet (facet $facet) {
        if (!$this->isRealFacet($facet)) return false;
        $temps = $this->importer->read ($facet->getLat (), $facet->getLon ());
        $vv = $this->importer->_getMaxMinMeanByVarName ($temps, $this->varname);
        $scr = scalarFactory::makeMetres ($vv, $this);
        $td = new temporalDatum ($this->getPalaeoTime (), $scr);
        $sd = new spatialDatum ($facet, $td);
        return $sd;
    }
    public function getElevationFromFacet (facet $facet) {
        if ($this->isRealFacet($facet)) return $this->getRealElevationFromFacet ($facet);
        // bug:
        $scr = $this->getInterpolatedValueFromFacet ($facet);
        
        $td = new temporalDatum ($this->getPalaeoTime (), $scr);
        $sd = new spatialDatum ($facet, $td);
        return $sd;
    }


    //public function getInterpolatedValueFromFacet (facet $facet) {}

    function getPalaeoTime ($timeName = NULL) {
        if ($timeName === NULL)
            $timeName = $this->timename;
        return self::wctcToPalaeoTime ($timeName);
    }

    static function wctcToPalaeoTime ($timeName) {
        $yearsKA = 0;
        switch ($timeName) {
            /*case PMIP2::T_LGM_21KA:
                $yearsKA = 21;
                break;
            case PMIP2::T_MID_HOLOCENE_6KA:
                $yearsKA = 6;
                break;*/
            case self::T_PRE_INDUSTRIAL_0KA:
                $yearsKA = .1;
                break;
            default:
                throw new Exception ("Unknown timeframe in " . __CLASS__ . "::" . __FUNCTION__ . ": " . addslashes ($this->timename));
        }
        $pt = new palaeoTime (1000.0 * $yearsKA);
        return $pt;
    }

    public static function getBlankScalar ($iVal, dataSet $ds) {
        switch ($ds->varname) {
            case self::ALT_VAR:
                return scalarFactory::makeMetres(null, $ds);
                break;
            default:
                return scalarFactory::makeKelvin(null, $ds);
                break;
        }
    }

}