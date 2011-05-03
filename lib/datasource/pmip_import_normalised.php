<?php


//include ("data_interfaces.php");
include ("pmip2/pmip_import.php");

class pmip extends dataSet {
    
    private $importer = NULL;
    
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
        if (get_class ($facet) != 'latLon')
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
    
    function getRealValueFromFacet (facet $facet) {
        $temps = $this->importer->_extractTemps ($facet->getLat (), $facet->getLon (), $this->varname, $this->timename, $this->modelname);
        $scr = scalarFactory::makeKelvin ($this->importer->_getMaxMin ($temps, $this->varname), $this);
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
        $n *= ($n < 0) ? -1 : 1;
        return ($n + 0.0 == ((int) $n) + 0.0) ? FALSE : TRUE;
    }
    
}


?>