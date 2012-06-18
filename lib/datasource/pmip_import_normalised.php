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

require_once ("pmip_import.php");

class pmip extends dataSet {
    
    public $importer = NULL;
    
    // These contain values of constants from the base PMIP2 importer class
    private $varname;
    private $timename;
    private $modelname;
    
    
    function __construct ($varname = NULL, $timename = NULL, $modelname = NULL) {
        if ($varname === NULL)
            $varname = PMIP2::TMIN_VAR;
        if ($timename === NULL)
            $timename = PMIP2::T_PRE_INDUSTRIAL_0KA;
        if ($modelname === NULL)
            $modelname = PMIP2::MODEL_HADCM3M2;
        
        $this->varname = $varname;
        $this->timename = $timename;
        $this->modelname = $modelname;
        
        $this->importer = new PMIP2 ();
    }
    
    function getNearestRealFacets (facet $facet) {
        if (!is_object ($facet) || !is_a ($facet, '\ttkpl\latLon'))
            return FALSE;
        
        $lat = $facet->getLat ();
        $lon = $facet->getLon ();
        
        $av = $this->_isWN ($lat);
        $ov = $this->_isWN ($lon);
        
        $out = array ();
        $fa = floor ($lat);
        $ca = ceil ($lat);
        $fo = floor ($lon);
        $co = ceil ($lon);
        
        $out[] = new latLon ($fa, $fo);
        if ($ov == TRUE)
            $out[] = new latLon ($fa, $co);
        if ($av == TRUE) {
            $out[] = new latLon ($ca, $fo);
            if ($ov == TRUE)
                $out[] = new latLon ($ca, $co);
        }
        
        return $out;
        
    }
    
    function getRealValueFromFacet (facet $facet/*, $lookup_varname = null*/) {
        /*if ($lookup_varname === null) $lookup_varname = $this->varname;*/

        // Get an array of  monthly
        $temps = $this->importer->_extractTemps ($facet->getLat (), $facet->getLon (), $this->varname, $this->timename, $this->modelname);
        // this is a bit of a hack to force the use of the mean sine instead of max and min sines.
        //$temps = $this->importer->_extractTemps ($facet->getLat (), $facet->getLon (), PMIP2::TMEAN_VAR, $this->timename, PMIP2::MODEL_CCSM);
        
        $scr = scalarFactory::makeKelvin ($this->importer->_getMaxMinMeanByVarName ($temps, $this->varname), $this);
        // lower dimensional datum objects go closer to the scalar in the tree. why tree? don't know.
        $td = new temporalDatum ($this->getPalaeoTime (), $scr);
        $sd = new spatialDatum ($facet, $td);
        return $sd;
    }
    function getDayMinOffsetFromFacet (facet $facet) {
        $temps = $this->importer->_extractTemps ($facet->getLat (), $facet->getLon (), $this->varname, $this->timename, $this->modelname);

        //$scr = scalarFactory::makeKelvin ($this->importer->_getMaxMin ($temps, $this->varname), $this);
        $scr = scalarFactory::makeDays ($this->importer->_getDayMinOffset ($temps, $this->varname), $this);
        // lower dimensional datum objects go closer to the scalar in the tree. why tree? don't know.
        $td = new temporalDatum ($this->getPalaeoTime (), $scr);
        $sd = new spatialDatum ($facet, $td);
        return $sd;

    }
    function getPalaeoTime ($timeName = NULL) {
        if ($timeName === NULL)
            $timeName = $this->timename;
        return pmip::ptcToPalaeoTime ($timeName);
    }
    
    static function ptcToPalaeoTime ($timeName) {
        $yearsKA = 0;
        switch ($timeName) {
            case PMIP2::T_LGM_21KA:
                $yearsKA = 21;
                break;
            case PMIP2::T_MID_HOLOCENE_6KA:
                $yearsKA = 6;
                break;
            case PMIP2::T_PRE_INDUSTRIAL_0KA:
                $yearsKA = .1;
                break;
            default:
                throw new Exception ("Unknown timeframe in " . __CLASS__ . "::" . __FUNCTION__ . ": " . addslashes ($this->timename));
        }
        $pt = new palaeoTime (1000.0 * $yearsKA);
        return $pt;
    }
    
    function isRealFacet (facet $facet) {
        
        $lat = $facet->getLat ();
        $lon = $facet->getLon ();
        
        $av = $this->_isWN ($lat);
        $ov = $this->_isWN ($lon);
        
        return (!$av && !$ov) ? TRUE : FALSE;
        
    }
    
    function _isWN ($n) {
        /*$n *= ($n < 0) ? -1 : 1;
        return ($n + 0.0 == ((int) $n) + 0.0) ? FALSE : TRUE;*/
        return (round (abs($n)) == abs($n)) ? true : false;
    }
    
}


?>