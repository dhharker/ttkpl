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

?>
