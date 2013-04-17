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

interface dataSetInterface {
    public function getNearestRealFacets (facet $facet);
    public function isRealFacet (facet $facet);
    public function getRealValueFromFacet (facet $facet);
    public function getInterpolatedValueFromFacet (facet $facet);
    public function getPalaeoTime ();
    public static function getBlankScalar ($iVal, dataset $ds);
}

abstract class dataSet extends taUtils implements dataSetInterface {

    function getDataSetDescription () {
        return "Abstract dataset wrapper class.";
    }

    public static function getBlankScalar ($iVal = null, dataset $ds = null) {
        // once we're using  stuff other than temperature with this, will need
        // to implement in each class and use this here:
        //return new scalar ($a, $b);
        // until then use AUs because could be abs/rel deg C/K:
        return scalarFactory::makeAU ($iVal, $ds);
    }

    function getInterpolatedValueFromFacet (\ttkpl\facet $facet) {
        
        if ($this->isRealFacet ($facet))
            return $this->getRealValueFromFacet ($facet);

        $nearFacets = $this->getNearestRealFacets ($facet);
        
        $values = array ();
        $weights = array ();
        foreach ($nearFacets as $fi => $nf) {
            //debug ($this->getRealValueFromFacet($nf));
            //debug ($this->importer->currentHeader);
            // bug:
            //debug (sprintf ("get real value from facet %02.2f %03.2f", $nf->getLat(), $nf->getLon()));
            //$values[] = $this->getRealValueFromFacet($nf)->getScalar()->getValue();
            
            // In case where there's no data, do nothing
            $vv = $this->getRealValueFromFacet($nf)->getScalar()->getValue();
            if ($vv > -9000) { // Junk returned when no data./
                $values[] = $vv;
                $weights[] = $facet->distanceTo($nf)->getValue();
            }
            //$weights[$fi] = $weights[$fi];
        }
        
        if (count ($values) > 0)
            $wm = $this->invWeightedMean ($values, $weights);
        else // ...in case there are NO valid data points nearby, just return 0 and be done with it :p
            $wm = 0;
        
        //debug ($nearFacets); debug (compact ('values', 'weights', 'wm'));
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
        if (is_object ($in) && is_a ($in, '\ttkpl\scalar'))
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

class csvData implements \Iterator {

    public $filename = NULL;
    public $data = array ();
    public $indexedData = array ();
    public $titlesRow1 = TRUE;
    public $titles = array (NULL);

    function __construct ($filename, $tr1 = TRUE) {
        return $this->_init ($filename, $tr1);
        mb_internal_encoding("UTF-8");
        
    }
    function _init ($filename, $tr1) {

        $this->titlesRow1 = ($tr1 == true) ? TRUE : FALSE;
        $this->filename = $filename;
        $csv = FALSE;
        if (!\file_exists ($filename))
            throw new \exception ("Couldn't find file: " . $filename);
        elseif (!$fh = fopen ($filename, 'r'))
            throw new \exception ("Couldn't open " . $filename);
        else {

            if ($this->titlesRow1) {
                $row = fgetcsv ($fh);
                $this->titles = $row;
            }
            while (($row = fgetcsv ($fh)) !== FALSE) {
                $this->data[] = $row;
                $this->indexedData[$row[0]] = $row[1];
            }

            $this->rewind ();



            return true;
        }

        return false;

    }

    function export ($filename) {
        if (file_exists ($filename) && !unlink ($filename))
            throw new exception ("Couldn't remove existing output file: " . $filename . " (check permissions).");
        $fh = fopen ($filename, 'w');
        if (!$fh)
            throw new exception ("Couldn't open " . $filename . " for writing.");
        else {
            //fprintf($fh, chr(0xEF).chr(0xBB).chr(0xBF));
            if ($this->titlesRow1) {
                fputcsv ($fh, $this->titles);
            }
            $this->rewind();
            do {
                fputcsv ($fh, $this->current());
            } while ($this->next ());
            fclose ($fh);
            $this->rewind();
            return true;
        }
        return false;
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
    public $nTimes = 0;


    function __construct ($csvFile) {
        $this->csv = new csvData ($csvFile);
        $this->times = array_keys ($this->csv->indexedData);
        $this->nTimes = count ($this->times);
        asort ($this->times, SORT_NUMERIC);
    }

    function getNearestRealFacets (facet $facet) {
        if ($this->isRealFacet ($facet))
            return array ($facet); //return array ($this->getRealValueFromFacet ($facet));

        $this->csv->rewind ();
        $min = $this->times[0];
        $maxi = $this->nTimes - 1;
        $max = $this->times[$maxi];
        $cmp = $facet->getYearsBp ();


        if ($cmp <= $min) {
            $return = array (new palaeoTime ($min+0.0, $this));
        }
        elseif ($cmp >= $max) {
            $return = array (new palaeoTime ($max, $this));
        }
        else {
            for ($i = 0; $i < $maxi; $i++) {
                if ($this->times[$i] < $cmp && $this->times[$i+1] > $cmp) {
                    $return = array (new palaeoTime ($this->times[$i] + 0, $this),
                                     new palaeoTime ($this->times[$i+1] + 0, $this));
                    break;
                }
            }
        }
        return $return;
        
    }
/*    function getRealValueFromFacet (facet $facet) {
        return (isset ($this->csv->indexedData[$facet->getYearsBp ()])) ? $this->csv->indexedData[$facet->getYearsBp ()] : FALSE;
    }*/
    function getInterpolatedValueFromFacet (facet $facet) {
        $points = $this->getNearestRealFacets ($facet);
        
        if (count ($points) == 1) // || $this->isRealFacet ($facet))
            return $this->getRealValueFromFacet ($points[0]);

        $v = array (); $w = array ();
        foreach ($points as $point) {
            $tmp = $this->getRealValueFromFacet ($point);
            if ($tmp === false) {
                //debug ($this->cleanse ($facet));
                //debug ($this->cleanse ($tmp));
                throw new \Exception("Unable to read result!");
                return false;
            }
            if (!\is_a($tmp, '\ttkpl\datum')) {
                //debug ($this->cleanse ($point));
                //debug ($this->cleanse ($tmp));
                throw new \Exception("Result wasn't a datum");
            }
            $tmpv = $tmp->getScalar()->getValue();
            $v[] = $tmpv;
            $w[] = abs ($facet->distanceTo ($point));// + 1E-9;
        }
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