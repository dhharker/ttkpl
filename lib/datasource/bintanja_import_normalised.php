<?php

/*
 * @todo update csv path and make more consistent
 * 
 */

// include ("data_interfaces.php");
include ("util/csv.php");


abstract class csvTimeSeries extends dataSet {
        
    public $csv = NULL;
    public $times = array ();
    
    function __construct ($csvFile) {
        $this->csv = new csvData ($csvFile);
        $this->times = array_keys ($this->csv->indexedData);
        asort ($this->times, SORT_NUMERIC);
    }
    
    function getNearestRealFacets (facet $facet) {
        if ($this->isRealFacet ($facet))
            return array ($facet); //return array ($this->getRealValueFromFacet ($facet));
        
        $this->csv->rewind ();
        $min = -9E20;
        $max = 9E20;
        $cmp = $facet->getYearsBp ();
        
        foreach ($this->times as $time)
            if ($time < $cmp && $time > $min)
                $min = $time;
            elseif ($time > $cmp && $time < $max)
                $max = $time;
        
        $lbF = new palaeoTime ($min, $this);
        $ubF = new palaeoTime ($max, $this);
        return array ($lbF, $ubF);
        
    }
/*    function getRealValueFromFacet (facet $facet) {
        return (isset ($this->csv->indexedData[$facet->getYearsBp ()])) ? $this->csv->indexedData[$facet->getYearsBp ()] : FALSE;
    }*/
    function getInterpolatedValueFromFacet (facet $facet) {
        $points = $this->getNearestRealFacets ($facet);
        if (count ($points) == 1) // || $this->isRealFacet ($facet))
            return $this->getRealValueFromFacet ($facet);
        
        $v = array (); $w = array ();
        foreach ($points as $point) {
            $tmp = $this->getRealValueFromFacet ($point);
            //echo "-$tmp-";
            $tmpv = $tmp->getScalar()->getValue();
            $v[] = $tmpv;
            $w[] = abs ($facet->distanceTo ($point));// + 1E-9;
        }
        //print_r ($w);
        //die ("\nw\n");
        $intVal = $this->invWeightedMean ($v, $w);
        $scr = scalarFactory::makeKelvinAnomaly ($intVal, $this);
        return new temporalDatum ($facet, $scr);
    }
    function getRealValueFromFacet (facet $facet) {
        $mta = (isset ($this->csv->indexedData[$facet->getYearsBp ()])) ? $this->csv->indexedData[$facet->getYearsBp ()] : FALSE;
        if ($mta !== FALSE) {
            $scr = scalarFactory::makeKelvinAnomaly ($mta, $this);
            return new temporalDatum ($facet, $scr);
        }
        else
            return FALSE;
    }
    function isRealFacet (facet $facet) {
        return (in_array ($facet->getYearsBp (), $this->times)) ? TRUE : FALSE;
    }
    function getPalaeoTime () {
        return new palaeoTime ($this->times[$this->csv->key ()]);
    }
    
}

class bintanja extends csvTimeSeries {
    function __construct () {
        parent::__construct ("bintanja/bintanja.csv");
    }
}

?>