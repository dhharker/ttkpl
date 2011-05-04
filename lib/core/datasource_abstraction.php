<?php
/* 
 * 
 */


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

class csvData implements Iterator {

    public $filename = NULL;
    public $data = array ();
    public $indexedData = array ();
    public $titlesRow1 = TRUE;
    public $titles = array (NULL);

    function __construct ($filename, $tr1 = TRUE) {
        return $this->_init ($filename, $tr1);
    }
    function _init ($filename, $tr1) {

        $this->titlesRow1 = ($tr1) ? TRUE : FALSE;
        $this->filename = $filename;
        $csv = FALSE;
        if (!file_exists ($filename))
            throw new exception ("Couldn't find file: " . $filename);
        elseif (!$fh = fopen ($filename, 'r'))
            throw new exception ("Couldn't open " . $filename);
        else {

            while (($row = fgetcsv ($fh)) !== FALSE) {
                $this->data[] = $row;
                $this->indexedData[$row[0]] = $row[1];

            }

            if ($this->titlesRow1) {
                $this->titles = array_shift ($this->data);
                $this->rewind ();
            }

            $this->rewind ();



            return true;
        }

        return false;

    }

    function export ($filename) {
        if (file_exists ($filename) && !unlink ($filename))
            throw new exception ("Couldn't remove existing output file: " . $filename . " (check permissions).");
        elseif (!$fh = fopen ($filename, 'w'))
            throw new exception ("Couldn't open " . $filename . " for writing.");
        else {
            if ($this->titlesRow1) {
                fputcsv ($fh, $this->titles);
            }
            $this->rewind();
            do {
                fputcsv ($fh, $this->current());
            } while ($this->next ());
            fclose ($fh);
            $this->rewind();
        }
    }

    function addColumn ($name) {
        if ($this->getColumn ($name) === FALSE) {
            $nci = max (array_keys ($this->titles)) + 1;
            $this->titles[$nci] = $name;
            return $nci;
        }
        else
            return FALSE;
    }
    function getColumn ($name) {
        if (!isset ($this->colInd[$name])) {
            $i = array_search ($name, $this->titles);
            if ($i !== FALSE)
                $this->colInd[$name] = $i;
            else
                return FALSE;
        }
        return $this->colInd[$name];
    }
    function setColumn ($name, $value) {
        if ($this->getColumn ($name) === FALSE)
            $this->addColumn ($name);
        $this->data[$this->key()][$this->getColumn ($name)] = $value;
    }

    function rewind () {
        reset ($this->data);
    }
    function current () {
        return current ($this->data);
    }
    function key () {
        return key ($this->data);
    }
    function next () {
        return next ($this->data);
    }
    function valid () {
        return !in_array ($this->current (), array (NULL, FALSE));
    }

}

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